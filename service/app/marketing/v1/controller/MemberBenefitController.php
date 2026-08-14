<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\marketing\v1\controller;

use app\common\BaseController;
use app\model\Coupon;
use app\model\User;
use app\model\UserCoupon;
use app\model\UserMemberCard;
use support\Db;
use support\Log;
use Webman\Http\Request;

/**
 * 会员权益控制器
 * 根据会员等级/卡片返回对应权益、生日福利
 */
class MemberBenefitController extends BaseController
{
    /**
     * 会员权益列表
     * GET /api/marketing/benefits
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;

        $user = User::with('technicianProfile')->find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        // 获取有效会员卡
        $memberCards = UserMemberCard::where('user_id', $userId)
            ->where('status', 'active')
            ->with('card')
            ->orderBy('level', 'desc')
            ->get()
            ->map(function ($uc) {
                $card = $uc->card;
                return [
                    'id' => $uc->id,
                    'card_id' => $card->id ?? null,
                    'card_name' => $card->name ?? '',
                    'level' => $card->level ?? 0,
                    'expire_at' => $uc->expire_at,
                    'remaining_times' => $uc->remaining_times ?? 0,
                    'benefits' => $card->benefits ?? [],
                ];
            });

        // 汇总权益
        $allBenefits = [];
        foreach ($memberCards as $card) {
            if (!empty($card['benefits'])) {
                $benefits = is_array($card['benefits']) ? $card['benefits'] : [];
                foreach ($benefits as $benefit) {
                    $allBenefits[] = [
                        'name' => $benefit['name'] ?? '',
                        'description' => $benefit['description'] ?? '',
                        'from_card' => $card['card_name'],
                    ];
                }
            }
        }

        return $this->success([
            'member_cards' => $memberCards,
            'benefits' => $allBenefits,
        ]);
    }

    /**
     * 生日福利查询
     * GET /api/marketing/benefits/birthday
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function birthday(Request $request)
    {
        $userId = $request->user_id;

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $today = date('m-d');

        // 检查生日：从用户资料获取生日信息（如果设有 birthday 字段）
        $birthdayField = $user->getAttribute('birthday');
        $isBirthday = false;

        if ($birthdayField) {
            $birthdayMd = date('m-d', strtotime($birthdayField));
            $isBirthday = ($birthdayMd === $today);
        }

        $result = [
            'is_birthday' => $isBirthday,
            'today' => $today,
            'coupon_generated' => false,
        ];

        // 如果今天是生日，自动生成生日优惠券
        if ($isBirthday) {
            // 检查今年是否已生成过生日券
            $year = date('Y');
            $alreadyReceived = UserCoupon::where('user_id', $userId)
                ->whereHas('coupon', function ($query) {
                    $query->where('type', 'birthday');
                })
                ->whereYear('created_at', $year)
                ->exists();

            if ($alreadyReceived) {
                $result['coupon_generated'] = false;
                $result['message'] = '今年的生日优惠券已领取';
            } else {
                // 查找可用的生日优惠券模板
                $birthdayCoupon = Coupon::where('type', 'birthday')
                    ->where('status', 1)
                    ->where('remain_qty', '>', 0)
                    ->first();

                if ($birthdayCoupon) {
                    Db::beginTransaction();
                    try {
                        $birthdayCoupon->decrement('remain_qty');

                        $userCoupon = UserCoupon::create([
                            'id' => UserCoupon::generateId(),
                            'user_id' => $userId,
                            'coupon_id' => $birthdayCoupon->id,
                            'status' => 'available',
                            'received_at' => date('Y-m-d H:i:s'),
                        ]);

                        Db::commit();

                        $result['coupon_generated'] = true;
                        $result['coupon'] = [
                            'id' => $userCoupon->id,
                            'coupon_id' => $birthdayCoupon->id,
                            'name' => $birthdayCoupon->name,
                            'amount' => $birthdayCoupon->amount,
                            'min_amount' => $birthdayCoupon->min_amount,
                            'end_at' => $birthdayCoupon->end_at,
                        ];
                    } catch (\Throwable $e) {
                        Db::rollBack();
                        // M3: 内部异常详情仅记日志，对外返回通用文案
                        Log::error('[MemberBenefitController] birthday coupon create failed: ' . $e->getMessage());
                        return $this->error('生日券生成失败，请稍后重试');
                    }
                } else {
                    $result['message'] = '暂无可用生日优惠券';
                }
            }
        }

        return $this->success($result);
    }
}
