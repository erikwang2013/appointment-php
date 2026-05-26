<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\CheckIn;
use app\model\UserPoints;
use support\Db;
use Webman\Http\Request;

/**
 * 签到控制器
 * 每日签到奖励积分，连续签到有额外奖励
 */
class CheckInController extends BaseController
{
    // 每日签到基础积分
    private const BASE_POINTS = 10;
    // 连续签到 7 天额外奖励
    private const STREAK_BONUS_DAYS = 7;
    private const STREAK_BONUS_POINTS = 20;

    /**
     * 每日签到
     * POST /api/user/check-in
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function store(Request $request)
    {
        $userId = $request->user_id;
        $today = date('Y-m-d');

        // 检查今日是否已签到
        $existing = CheckIn::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        if ($existing) {
            return $this->error('今日已签到', 400, [
                'checked_in' => true,
                'points_awarded' => $existing->points_awarded,
                'consecutive_days' => $existing->consecutive_days,
            ]);
        }

        // 计算连续签到天数
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $lastCheckIn = CheckIn::where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->first();

        if ($lastCheckIn && $lastCheckIn->date === $yesterday) {
            $consecutiveDays = $lastCheckIn->consecutive_days + 1;
        } else {
            $consecutiveDays = 1;
        }

        // 计算奖励积分
        $pointsAwarded = self::BASE_POINTS;
        $bonus = 0;

        if ($consecutiveDays % self::STREAK_BONUS_DAYS === 0) {
            $bonus = self::STREAK_BONUS_POINTS;
            $pointsAwarded += $bonus;
        }

        Db::beginTransaction();
        try {
            // 创建签到记录
            $checkIn = CheckIn::create([
                'id' => CheckIn::generateId(),
                'user_id' => $userId,
                'date' => $today,
                'points_awarded' => $pointsAwarded,
                'consecutive_days' => $consecutiveDays,
            ]);

            // 发放积分
            UserPoints::create([
                'id' => UserPoints::generateId(),
                'user_id' => $userId,
                'type' => 'income',
                'points' => $pointsAwarded,
                'balance' => $pointsAwarded,
                'source' => 'check_in',
                'description' => '每日签到奖励'
                    . ($bonus > 0 ? " (含连续{$consecutiveDays}天奖励+{$bonus})" : ''),
            ]);

            Db::commit();

            return $this->success([
                'checked_in' => true,
                'points_awarded' => $pointsAwarded,
                'base_points' => self::BASE_POINTS,
                'bonus_points' => $bonus,
                'consecutive_days' => $consecutiveDays,
                'next_bonus_at' => self::STREAK_BONUS_DAYS - ($consecutiveDays % self::STREAK_BONUS_DAYS),
            ], '签到成功');
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->error('签到失败: ' . $e->getMessage());
        }
    }

    /**
     * 签到状态
     * GET /api/user/check-in/status
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function status(Request $request)
    {
        $userId = $request->user_id;
        $today = date('Y-m-d');

        // 今日签到状态
        $todayCheckIn = CheckIn::where('user_id', $userId)
            ->where('date', $today)
            ->first();

        // 连续签到天数
        $lastCheckIn = CheckIn::where('user_id', $userId)
            ->orderBy('date', 'desc')
            ->first();

        $consecutiveDays = 0;
        $checkedIn = false;

        if ($todayCheckIn) {
            $checkedIn = true;
            $consecutiveDays = $todayCheckIn->consecutive_days;
        } elseif ($lastCheckIn) {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            if ($lastCheckIn->date === $yesterday) {
                $consecutiveDays = $lastCheckIn->consecutive_days;
            }
        }

        $daysUntilBonus = self::STREAK_BONUS_DAYS - ($consecutiveDays % self::STREAK_BONUS_DAYS);
        if ($daysUntilBonus === self::STREAK_BONUS_DAYS) {
            $daysUntilBonus = 0;
        }

        return $this->success([
            'checked_in' => $checkedIn,
            'consecutive_days' => $consecutiveDays,
            'base_points' => self::BASE_POINTS,
            'streak_bonus_points' => self::STREAK_BONUS_POINTS,
            'streak_bonus_days' => self::STREAK_BONUS_DAYS,
            'days_until_bonus' => $daysUntilBonus,
        ]);
    }
}
