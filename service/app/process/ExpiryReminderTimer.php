<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\process;

use app\common\NotificationReminderService;
use app\model\Notification;
use app\model\UserCoupon;
use app\model\UserMemberCard;
use support\Db;
use support\Log;
use Workerman\Timer;

/**
 * 会员卡/优惠券到期提醒定时器
 *
 * 每 6 小时扫描一次到期前 3 天窗口内（end_at ∈ [now+3d, now+3d+6h)）的
 * 有效会员卡（erik_user_member_card status=active）与可用优惠券
 * （erik_user_coupon status=available，有效期以 erik_coupon.end_at 为准），
 * 为用户写站内通知（type=card_expiry / coupon_expiry），并挂接可配置降级的
 * 微信订阅消息（SCENE_EXPIRY，未配置 WECHAT_SUBSCRIBE_TEMPLATE_EXPIRY 时仅站内通知）。
 *
 * 防重机制（与 PointsExpiryTimer 同三层）：
 * 1. 处理时对卡/券行 lockForUpdate + 站内通知查重（user_id + type + order_id
 *    记来源卡/券 id），同来源至多一条提醒，并发进程在行锁上串行化；
 * 2. 扫描按 id 游标递增分页（BATCH_SIZE 一批），同一进程不重复扫到同一行；
 * 3. 订阅消息仅在通知行实际写入的扫描轮次产生，且推送成功才写 push_sent_at。
 */
class ExpiryReminderTimer
{
    /** 扫描间隔（秒）：6 小时低频 */
    private const SCAN_INTERVAL = 21600;

    /** 每批扫描行数 */
    private const BATCH_SIZE = 100;

    /** 提前提醒天数：到期前 3 天 */
    private const REMIND_AHEAD_DAYS = 3;

    private const NOTIFY_TYPE_CARD    = 'card_expiry';
    private const NOTIFY_TITLE_CARD   = '会员卡即将到期';
    private const NOTIFY_TYPE_COUPON  = 'coupon_expiry';
    private const NOTIFY_TITLE_COUPON = '优惠券即将到期';

    public function __construct()
    {
        Timer::add(self::SCAN_INTERVAL, function (): void {
            $this->scanAndRemind();
        });
    }

    /**
     * 扫描到期前 3 天内的会员卡与优惠券并提醒（幂等，可重复调用）
     *
     * 扫描范围：end_at ∈ (now, now + 3 天 + 一个扫描间隔]——既覆盖到期前 3 天
     * 阈值刚跨越的记录，也覆盖进程停机错过阈值后的补扫（防重靠通知查重兜底）。
     */
    public function scanAndRemind(): void
    {
        try {
            $remindedCard   = $this->scanCards();
            $remindedCoupon = $this->scanCoupons();

            if ($remindedCard + $remindedCoupon > 0) {
                Log::info('[ExpiryReminderTimer] Sent ' . $remindedCard . ' card + '
                    . $remindedCoupon . ' coupon expiry reminders');
            }
        } catch (\Throwable $e) {
            Log::error('[ExpiryReminderTimer] Scan error: ' . $e->getMessage()
                . "\n" . $e->getTraceAsString());
        }
    }

    // ── 会员卡 ──

    /** 扫描到期前 3 天的有效会员卡，返回本轮提醒数 */
    private function scanCards(): int
    {
        $lowerBound = $this->expiryLowerBound();
        $upperBound = $this->expiryUpperBound();
        $cursor     = 0;
        $sent       = 0;

        while (true) {
            $rows = UserMemberCard::with('card')
                ->where('status', 'active')
                ->where('end_at', '>', $lowerBound)
                ->where('end_at', '<=', $upperBound)
                ->where('id', '>', $cursor)
                ->orderBy('id')
                ->limit(self::BATCH_SIZE)
                ->get();

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $card) {
                try {
                    if ($this->processCard($card)) {
                        $sent++;
                    }
                } catch (\Throwable $e) {
                    Log::error('[ExpiryReminderTimer] Failed to remind card '
                        . ($card->id ?? 'unknown') . ': ' . $e->getMessage());
                }
                $cursor = max($cursor, (int) $card->id);
            }

            if ($rows->count() < self::BATCH_SIZE) {
                break; // 最后一批
            }
        }

        return $sent;
    }

    /** 处理单张会员卡：锁行 + 查重 + 写站内通知 + 订阅消息（幂等） */
    private function processCard(UserMemberCard $card): bool
    {
        $notificationId = Db::transaction(function () use ($card): ?string {
            // 重新查询并加锁，串行化并发扫描
            $locked = UserMemberCard::where('id', $card->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();
            if (!$locked) {
                return null; // 状态已变更（已过期/已停用）
            }

            // 幂等查重：同用户 + 同类型 + 同来源（order_id 记卡 id）视为已提醒
            if ($this->notificationExists((string) $card->user_id, self::NOTIFY_TYPE_CARD, (string) $card->id)) {
                return null;
            }

            $name = (string) ($card->card->name ?? '会员卡');
            $time = $card->end_at ? date('Y-m-d', strtotime((string) $card->end_at)) : '';

            $id = Notification::generateId();
            Db::table('erik_notification')->insert([
                'id'         => $id,
                'user_id'    => $card->user_id,
                'type'       => self::NOTIFY_TYPE_CARD,
                'title'      => self::NOTIFY_TITLE_CARD,
                'content'    => '您的会员卡「' . $name . '」将于 ' . $time . ' 到期，请及时使用。',
                'order_id'   => $card->id,
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return $id;
        });

        if ($notificationId === null) {
            return false;
        }

        $name = (string) ($card->card->name ?? '会员卡');
        $this->sendSubscribeExpiry((string) $card->user_id, $notificationId, $name,
            $card->end_at ? date('Y-m-d H:i', strtotime((string) $card->end_at)) : '');
        return true;
    }

    // ── 优惠券 ──

    /** 扫描到期前 3 天的可用优惠券，返回本轮提醒数 */
    private function scanCoupons(): int
    {
        $lowerBound = $this->expiryLowerBound();
        $upperBound = $this->expiryUpperBound();
        $cursor     = 0;
        $sent       = 0;

        while (true) {
            $rows = UserCoupon::with('coupon')
                ->where('status', 'available')
                ->whereHas('coupon', function ($q) use ($lowerBound, $upperBound): void {
                    $q->where('end_at', '>', $lowerBound)
                        ->where('end_at', '<=', $upperBound);
                })
                ->where('id', '>', $cursor)
                ->orderBy('id')
                ->limit(self::BATCH_SIZE)
                ->get();

            if ($rows->isEmpty()) {
                break;
            }

            foreach ($rows as $userCoupon) {
                try {
                    if ($this->processCoupon($userCoupon)) {
                        $sent++;
                    }
                } catch (\Throwable $e) {
                    Log::error('[ExpiryReminderTimer] Failed to remind coupon '
                        . ($userCoupon->id ?? 'unknown') . ': ' . $e->getMessage());
                }
                $cursor = max($cursor, (int) $userCoupon->id);
            }

            if ($rows->count() < self::BATCH_SIZE) {
                break; // 最后一批
            }
        }

        return $sent;
    }

    /** 处理单张优惠券：锁行 + 查重 + 写站内通知 + 订阅消息（幂等） */
    private function processCoupon(UserCoupon $userCoupon): bool
    {
        $notificationId = Db::transaction(function () use ($userCoupon): ?string {
            // 重新查询并加锁，串行化并发扫描
            $locked = UserCoupon::where('id', $userCoupon->id)
                ->where('status', 'available')
                ->lockForUpdate()
                ->first();
            if (!$locked) {
                return null; // 状态已变更（已使用/已过期/已转赠）
            }

            // 幂等查重：同用户 + 同类型 + 同来源（order_id 记券 id）视为已提醒
            if ($this->notificationExists((string) $userCoupon->user_id, self::NOTIFY_TYPE_COUPON, (string) $userCoupon->id)) {
                return null;
            }

            $name = (string) ($userCoupon->coupon->name ?? '优惠券');
            $time = $userCoupon->coupon && $userCoupon->coupon->end_at
                ? date('Y-m-d', strtotime((string) $userCoupon->coupon->end_at))
                : '';

            $id = Notification::generateId();
            Db::table('erik_notification')->insert([
                'id'         => $id,
                'user_id'    => $userCoupon->user_id,
                'type'       => self::NOTIFY_TYPE_COUPON,
                'title'      => self::NOTIFY_TITLE_COUPON,
                'content'    => '您的优惠券「' . $name . '」将于 ' . $time . ' 到期，请尽快使用。',
                'order_id'   => $userCoupon->id,
                'is_read'    => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return $id;
        });

        if ($notificationId === null) {
            return false;
        }

        $name = (string) ($userCoupon->coupon->name ?? '优惠券');
        $time = $userCoupon->coupon && $userCoupon->coupon->end_at
            ? date('Y-m-d H:i', strtotime((string) $userCoupon->coupon->end_at))
            : '';
        $this->sendSubscribeExpiry((string) $userCoupon->user_id, $notificationId, $name, $time);
        return true;
    }

    // ── 内部方法 ──

    /** 同用户 + 同类型 + 同来源（order_id）是否已有提醒通知 */
    private function notificationExists(string $userId, string $type, string $sourceId): bool
    {
        return Notification::where('user_id', $userId)
            ->where('type', $type)
            ->where('order_id', $sourceId)
            ->exists();
    }

    /** 到期提醒订阅消息钩子：未配置微信模板时仅站内通知（可配置降级）；失败不影响主流程 */
    private function sendSubscribeExpiry(string $userId, string $notificationId, string $name, string $time): void
    {
        (new NotificationReminderService())->sendSubscribeForNotification(
            NotificationReminderService::SCENE_EXPIRY,
            $userId,
            $notificationId,
            [
                'thing1' => ['value' => $this->truncate($name, 20)],
                'time2'  => ['value' => $time],
            ]
        );
    }

    /** 扫描下界：now（仅提醒尚未到期的卡/券，过期懒判定不产生噪音） */
    private function expiryLowerBound(): string
    {
        return date('Y-m-d H:i:s', time());
    }

    /** 扫描上界：now + 3 天 + 一个扫描间隔（覆盖阈值刚跨越 + 停机补扫，防重靠通知查重） */
    private function expiryUpperBound(): string
    {
        return date('Y-m-d H:i:s', time() + self::REMIND_AHEAD_DAYS * 86400 + self::SCAN_INTERVAL);
    }

    /** 按字符数截断（thing 字段值上限 20 字符，避免微信 47003 报错） */
    private function truncate(string $value, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $length);
        }
        return substr($value, 0, $length);
    }
}
