<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use app\model\Notification;
use app\model\Order;
use app\model\TechnicianProfile;
use app\model\TechnicianTierConfig;
use app\model\TechnicianTierLog;
use support\Db;
use support\Log;

/**
 * 技师等级自动评定服务
 *
 * 懒判定触发：技师完成订单（WorkController::complete）、用户提交评价（ReviewController::store）、
 * 技师查看个人资料（ProfileController::show）时调用 evaluate()。
 *
 * 评定数据实时统计（不依赖手工维护的字段）：
 *   订单量 = erik_order 中该技师 status=completed 的订单数；
 *   评分   = erik_order_review 中该技师可见评价的平均分（四舍五入 1 位小数）。
 * 评定后把 order_count/rating 回写 erik_technician_profile，保持 admin 端进度数据新鲜。
 *
 * 升降级规则：
 *   应得等级从高到低匹配 erik_technician_tier_config（order_count >= min_orders 且 rating >= min_rating），
 *   无任何匹配则归入最低等级。应得等级高于当前等级 → 自动升级；
 *   应得等级低于当前等级 → 触发降级，但默认被降级保护拦截（仅升级不降级），
 *   保护原因：等级绑定佣金率与价格系数，自动降级直接影响技师收入且易引发纠纷，
 *   数据异常下滑由 admin 手动兜底（TechnicianTierController::assign 评估参考 / update 调条件）。
 *   传入 $allowDowngrade=true（如后台人工重评）时才执行降级。
 *
 * 幂等：应得等级与 profile.tier_id 一致时只同步统计、不写日志不发通知。
 * 变更：每次升级/降级写 erik_technician_tier_log 并给技师发站内通知（type='tier'）。
 */
class TierRatingService
{
    /** 应得等级与当前一致，幂等跳过 */
    public const ACTION_NONE = 'none';
    /** 自动升级 */
    public const ACTION_UPGRADE = 'upgrade';
    /** 自动降级（仅 $allowDowngrade=true 时发生） */
    public const ACTION_DOWNGRADE = 'downgrade';
    /** 触发降级但被降级保护拦截，保持当前等级 */
    public const ACTION_KEEP = 'keep';

    /**
     * 评定一个技师的等级，必要时升级/降级并落日志、发通知
     */
    public static function evaluate(string $technicianId, bool $allowDowngrade = false): array
    {
        $profile = TechnicianProfile::find($technicianId);
        if (!$profile) {
            return [
                'technician_id' => $technicianId,
                'order_count'   => 0,
                'rating'        => 0.0,
                'tier_id'       => null,
                'tier_slug'     => '',
                'tier_name'     => '',
                'action'        => 'none',
                'old_tier_id'   => null,
                'new_tier_id'   => null,
                'changed'       => false,
                'reason'        => '技师档案不存在',
            ];
        }

        // 1. 实时统计
        $orderCount = (int) Db::table('erik_order')
            ->where('technician_id', $technicianId)
            ->where('status', Order::STATUS_COMPLETED)
            ->count();
        $avgRating = Db::table('erik_order_review')
            ->where('technician_id', $technicianId)
            ->avg('rating');
        $rating = round((float) $avgRating, 1);

        // 2. 匹配应得等级（从高到低；无匹配归最低等级）
        $tiers = TechnicianTierConfig::orderBy('sort', 'desc')->get();
        $target = null;
        foreach ($tiers as $tier) {
            if ($orderCount >= (int) $tier->min_orders && $rating >= (float) $tier->min_rating) {
                $target = $tier;
                break;
            }
        }
        if (!$target && $tiers->isNotEmpty()) {
            $target = $tiers->last();
        }

        $currentTierId = $profile->tier_id !== null ? (string) $profile->tier_id : null;
        $targetTierId  = $target ? (string) $target->id : null;

        // 幂等：应得等级未变，仅同步统计字段
        if ($currentTierId === $targetTierId) {
            $profile->order_count = $orderCount;
            $profile->rating = $rating;
            $profile->save();

            return self::result($technicianId, $orderCount, $rating, $target, self::ACTION_NONE,
                $currentTierId, $targetTierId, '等级未变化，幂等跳过');
        }

        $currentTier = $currentTierId ? TechnicianTierConfig::find($currentTierId) : null;
        $currentSort = $currentTier ? (int) $currentTier->sort : 0;
        $targetSort  = $target ? (int) $target->sort : 0;

        // 3. 升级
        if ($targetSort > $currentSort) {
            self::applyTier($profile, $orderCount, $rating, $target);
            self::recordChange($profile, $currentTierId, $targetTierId,
                '订单量' . $orderCount . '、评分' . $rating . ' 达到 ' . $target->name . ' 升级条件');
            self::notifyTechnician($profile, '恭喜升级为' . $target->name,
                '您的服务订单量与评分已达到' . $target->name . '标准，等级已自动升级。');

            return self::result($technicianId, $orderCount, $rating, $target, self::ACTION_UPGRADE,
                $currentTierId, $targetTierId, '自动升级');
        }

        // 4. 降级（默认被降级保护拦截：等级不变，new_tier_id 为实际保持的当前等级）
        if (!$allowDowngrade) {
            $profile->order_count = $orderCount;
            $profile->rating = $rating;
            $profile->save();

            return self::result($technicianId, $orderCount, $rating, $currentTier, self::ACTION_KEEP,
                $currentTierId, $currentTierId, '触发降级但被降级保护拦截（仅升级不降级）');
        }

        self::applyTier($profile, $orderCount, $rating, $target);
        self::recordChange($profile, $currentTierId, $targetTierId,
            '订单量' . $orderCount . '、评分' . $rating . ' 低于 ' . $target->name . ' 条件，自动降级');
        self::notifyTechnician($profile, '等级调整为' . $target->name,
            '您的服务订单量与评分未达' . $target->name . '标准，等级已调整为' . $target->name . '。');

        return self::result($technicianId, $orderCount, $rating, $target, self::ACTION_DOWNGRADE,
            $currentTierId, $targetTierId, '自动降级');
    }

    /**
     * 写 profile 的等级与统计字段（不落日志）
     */
    private static function applyTier(TechnicianProfile $profile, int $orderCount, float $rating, ?TechnicianTierConfig $target): void
    {
        $profile->tier_id = $target ? $target->id : null;
        $profile->order_count = $orderCount;
        $profile->rating = $rating;
        $profile->save();
    }

    /**
     * 等级变更落日志（erik_technician_tier_log）
     */
    private static function recordChange(TechnicianProfile $profile, ?string $oldTierId, ?string $newTierId, string $reason): void
    {
        try {
            TechnicianTierLog::create([
                'id'             => TechnicianTierLog::generateId(),
                'technician_id'  => $profile->id,
                'old_tier_id'    => $oldTierId,
                'new_tier_id'    => $newTierId,
                'reason'         => $reason,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[TierRatingService] recordChange failed: ' . $e->getMessage());
        }
    }

    /**
     * 站内通知技师（type='tier'，非阻塞：失败仅记日志，不影响评定主流程）
     */
    private static function notifyTechnician(TechnicianProfile $profile, string $title, string $content): void
    {
        try {
            Notification::create([
                'id'       => Notification::generateId(),
                'user_id'  => $profile->user_id,
                'type'     => 'tier',
                'title'    => $title,
                'content'  => $content,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[TierRatingService] notifyTechnician failed: ' . $e->getMessage());
        }
    }

    private static function result(string $technicianId, int $orderCount, float $rating,
                                   ?TechnicianTierConfig $target, string $action,
                                   ?string $oldTierId, ?string $newTierId, string $reason): array
    {
        return [
            'technician_id' => $technicianId,
            'order_count'   => $orderCount,
            'rating'        => $rating,
            'tier_id'       => $newTierId,
            'tier_slug'     => $target ? $target->slug : '',
            'tier_name'     => $target ? $target->name : '',
            'action'        => $action,
            'old_tier_id'   => $oldTierId,
            'new_tier_id'   => $newTierId,
            'changed'       => $action === self::ACTION_UPGRADE || $action === self::ACTION_DOWNGRADE,
            'reason'        => $reason,
        ];
    }
}
