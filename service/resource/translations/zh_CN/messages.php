<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

/**
 * 中文业务消息翻译
 * 涵盖所有业务模块的 API 错误和成功消息
 */
return [
    // ── 认证 ──
    'login_success' => '登录成功',
    'register_success' => '注册成功',
    'logout_success' => '已退出登录',
    'password_changed' => '密码修改成功',
    'phone_changed' => '手机号更换成功',
    'password_error' => '密码错误',
    'old_password_error' => '原密码错误',
    'account_not_found' => '账号不存在',
    'account_disabled' => '账号已被禁用',
    'token_expired' => '登录已过期，请重新登录',
    'token_invalid' => '无效的登录凭证',
    'login_failed' => '登录失败，请检查账号密码',

    // ── 验证码 ──
    'phone_required' => '请输入手机号码',
    'phone_invalid' => '请输入正确的手机号码',
    'code_error' => '验证码错误或已过期',
    'code_sent' => '验证码发送成功',
    'code_send_failed' => '验证码发送失败',
    'code_send_too_frequent' => '验证码发送过于频繁，请稍后再试',

    // ── 订单 ──
    'order_not_found' => '订单不存在',
    'order_created' => '订单创建成功',
    'order_cancelled' => '订单已取消',
    'order_paid' => '订单支付成功',
    'order_confirmed' => '订单已确认',
    'order_completed' => '订单已完成',
    'order_refunding' => '退款申请已提交',
    'refund_success' => '退款申请已提交',
    'refund_failed' => '退款申请失败',
    'verify_success' => '核销成功',
    'cannot_cancel' => '当前订单状态不允许取消',
    'cannot_refund' => '当前订单状态不允许退款',
    'order_time_conflict' => '该时段已有预约，请选择其他时间',

    // ── 技师 ──
    'technician_locked' => '该时段技师已被锁定，请稍后再试',
    'technician_not_found' => '技师不存在',
    'technician_offline' => '技师已下线',
    'technician_busy' => '技师当前繁忙',
    'schedule_updated' => '排班更新成功',
    'withdraw_success' => '提现申请已提交',

    // ── 权限 ──
    'permission_denied' => '没有操作权限',
    'not_technician' => '非技师身份无法操作',
    'role_switch_success' => '角色切换成功',

    // ── 用户 ──
    'user_not_found' => '用户不存在',
    'profile_updated' => '资料更新成功',
    'nickname_too_long' => '昵称长度不能超过50个字符',
    'gender_invalid' => '无效的性别参数',
    'password_too_short' => '密码长度不能少于6位',
    'password_not_match' => '两次输入的新密码不一致',
    'phone_duplicated' => '该手机号已被其他账号绑定',
    'account_cancelled' => '账号已注销',

    // ── 地址 ──
    'address_created' => '地址添加成功',
    'address_updated' => '地址更新成功',
    'address_deleted' => '地址已删除',
    'address_not_found' => '地址不存在',

    // ── 收藏 ──
    'favorite_added' => '收藏成功',
    'favorite_removed' => '已取消收藏',
    'favorite_exists' => '已收藏，请勿重复收藏',

    // ── 营销/优惠券 ──
    'coupon_received' => '优惠券领取成功',
    'coupon_not_available' => '优惠券不可用',
    'coupon_expired' => '优惠券已过期',
    'card_buy_success' => '会员卡购买成功',
    'card_not_found' => '会员卡不存在',
    'point_insufficient' => '积分不足',
    'gift_card_redeemed' => '礼品卡兑换成功',
    'gift_card_invalid' => '礼品卡无效或已使用',
    'check_in_success' => '签到成功',
    'check_in_duplicated' => '今日已签到',

    // ── 门店 ──
    'store_not_found' => '门店不存在',
    'queue_number_taken' => '取号成功',
    'queue_already_waiting' => '您已在排队中',
    'queue_cancelled' => '已取消排队',

    // ── 考试 ──
    'exam_started' => '考试已开始',
    'exam_submitted' => '考试已提交',
    'exam_passed' => '考试已通过',
    'exam_failed' => '考试未通过',
    'exam_not_found' => '考试不存在',
    'exam_already_attempted' => '您已完成此考试',
    'exam_time_up' => '考试时间已到',

    // ── 通知 ──
    'notification_read' => '已标记为已读',
    'notification_all_read' => '全部已标记为已读',
    'device_registered' => '设备注册成功',
    'device_unregistered' => '设备已注销',

    // ── 通用 ──
    'success' => '操作成功',
    'error' => '操作失败',
    'param_error' => '参数错误',
    'server_error' => '服务器内部错误',
    'network_error' => '网络错误，请稍后再试',
    'data_not_found' => '数据不存在',
    'page_not_found' => '页面不存在',
    'too_many_requests' => '请求过于频繁，请稍后再试',
    'maintenance_mode' => '系统维护中，请稍后再试',
    'version_outdated' => '客户端版本过低，请更新后再试',
    'invalid_signature' => '签名无效',
    'file_upload_failed' => '文件上传失败',
    'file_too_large' => '文件大小超出限制',
    'file_type_not_allowed' => '不支持的文件类型',
    'share_success' => '分享成功',
    'referral_success' => '推荐成功',
];
