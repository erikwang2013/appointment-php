<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use support\Request;
use support\Response;

class DocsController
{
    public function index(Request $request): Response
    {
        return json($this->buildSpec());
    }

    private function buildSpec(): array
    {
        $baseUrl = rtrim((string) config('app.url', 'http://localhost:8787'), '/');

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title'   => '预约服务系统 - 业务API（客户端）',
                'description' => '微信小程序和Flutter APP的业务接口。JWT Bearer认证，ID通过hashids编码。',
                'version' => '1.0.0',
                'contact' => ['name' => 'erik', 'email' => 'erik@erik.xyz', 'url' => 'https://erik.xyz'],
            ],
            'servers' => [['url' => $baseUrl, 'description' => '服务端']],
            'security' => [['bearerAuth' => []]],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => ['type' => 'http', 'scheme' => 'bearer', 'bearerFormat' => 'JWT'],
                ],
                'schemas' => [
                    'ApiResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'code'    => ['type' => 'integer'],
                            'message' => ['type' => 'string'],
                            'data'    => ['type' => 'object'],
                        ],
                    ],
                    'AuthResult' => [
                        'type' => 'object',
                        'properties' => [
                            'token' => ['type' => 'string', 'description' => 'JWT Bearer Token'],
                            'user'  => ['$ref' => '#/components/schemas/UserProfile'],
                        ],
                    ],
                    'UserProfile' => [
                        'type' => 'object',
                        'properties' => [
                            'id'            => ['type' => 'string', 'description' => 'hashids编码'],
                            'phone'         => ['type' => 'string', 'description' => '138****8000格式'],
                            'nickname'      => ['type' => 'string'],
                            'avatar'        => ['type' => 'string', 'format' => 'uri'],
                            'gender'        => ['type' => 'integer', 'description' => '0=未知 1=男 2=女'],
                            'user_type'     => ['type' => 'string', 'enum' => ['customer', 'technician']],
                            'active_role'   => ['type' => 'string', 'enum' => ['customer', 'technician']],
                            'referral_code' => ['type' => 'string'],
                            'created_at'    => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'ServiceItem' => [
                        'type' => 'object',
                        'properties' => [
                            'id'             => ['type' => 'string'],
                            'name'           => ['type' => 'string'],
                            'cover_image'    => ['type' => 'string'],
                            'price'          => ['type' => 'string'],
                            'original_price' => ['type' => 'string'],
                            'duration'       => ['type' => 'integer'],
                            'sales_volume'   => ['type' => 'integer'],
                        ],
                    ],
                    'ServiceDetail' => [
                        'allOf' => [
                            ['$ref' => '#/components/schemas/ServiceItem'],
                            ['type' => 'object', 'properties' => [
                                'description' => ['type' => 'string'],
                                'images'      => ['type' => 'array', 'items' => ['type' => 'string']],
                                'specs'       => ['type' => 'array', 'items' => ['type' => 'object']],
                                'reviews'     => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Review']],
                            ]],
                        ],
                    ],
                    'Review' => [
                        'type' => 'object',
                        'properties' => [
                            'id'         => ['type' => 'string'],
                            'user_name'  => ['type' => 'string'],
                            'avatar'     => ['type' => 'string'],
                            'rating'     => ['type' => 'integer', 'minimum' => 1, 'maximum' => 5],
                            'content'    => ['type' => 'string'],
                            'images'     => ['type' => 'array', 'items' => ['type' => 'string']],
                            'created_at' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'TechnicianCard' => [
                        'type' => 'object',
                        'properties' => [
                            'id'                 => ['type' => 'string'],
                            'name'               => ['type' => 'string'],
                            'avatar'             => ['type' => 'string'],
                            'rating'             => ['type' => 'number'],
                            'order_count'        => ['type' => 'integer'],
                            'favorite_count'     => ['type' => 'integer'],
                            'distance'           => ['type' => 'integer', 'description' => '距离(米)'],
                            'earliest_available'   => ['type' => 'string', 'format' => 'date-time'],
                            'is_available'       => ['type' => 'boolean'],
                        ],
                    ],
                    'Store' => [
                        'type' => 'object',
                        'properties' => [
                            'id'             => ['type' => 'string'],
                            'name'           => ['type' => 'string'],
                            'address'        => ['type' => 'string'],
                            'phone'          => ['type' => 'string'],
                            'business_hours' => ['type' => 'string'],
                            'distance'       => ['type' => 'integer'],
                        ],
                    ],
                    'Order' => [
                        'type' => 'object',
                        'properties' => [
                            'id'              => ['type' => 'string'],
                            'order_no'        => ['type' => 'string'],
                            'order_type'      => ['type' => 'string', 'enum' => ['appointment', 'product']],
                            'total_amount'    => ['type' => 'string'],
                            'discount_amount' => ['type' => 'string'],
                            'paid_amount'     => ['type' => 'string'],
                            'status'          => ['type' => 'string', 'enum' => ['pending', 'paid', 'confirmed', 'serving', 'completed', 'cancelled', 'refunded']],
                            'service_time'    => ['type' => 'string', 'format' => 'date-time'],
                            'remark'          => ['type' => 'string'],
                            'items'           => ['type' => 'array', 'items' => ['type' => 'object']],
                            'created_at'      => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'TechnicianEarning' => [
                        'type' => 'object',
                        'properties' => [
                            'id'          => ['type' => 'string'],
                            'type'        => ['type' => 'string', 'enum' => ['commission', 'bonus', 'penalty', 'subsidy', 'attendance']],
                            'amount'      => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'status'      => ['type' => 'string', 'enum' => ['pending', 'settled', 'withdrawn']],
                            'created_at'  => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'Coupon' => [
                        'type' => 'object',
                        'properties' => [
                            'id'         => ['type' => 'string'],
                            'name'       => ['type' => 'string'],
                            'type'       => ['type' => 'string', 'enum' => ['fixed', 'percent']],
                            'amount'     => ['type' => 'string'],
                            'min_amount' => ['type' => 'string'],
                            'status'     => ['type' => 'string', 'enum' => ['available', 'used', 'expired']],
                            'start_at'   => ['type' => 'string', 'format' => 'date-time'],
                            'end_at'     => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'MemberCard' => [
                        'type' => 'object',
                        'properties' => [
                            'id'          => ['type' => 'string'],
                            'name'        => ['type' => 'string'],
                            'type'        => ['type' => 'string', 'enum' => ['month', 'vip', 'times']],
                            'total_times' => ['type' => 'integer'],
                            'used_times'  => ['type' => 'integer'],
                            'start_at'    => ['type' => 'string', 'format' => 'date-time'],
                            'end_at'      => ['type' => 'string', 'format' => 'date-time'],
                            'status'      => ['type' => 'string', 'enum' => ['active', 'expired', 'used_up']],
                        ],
                    ],
                ],
            ],
            'paths' => $this->buildPaths(),
        ];
    }

    private function buildPaths(): array
    {
        $p = [];

        // 公开 - 验证码
        $this->add($p, '/api/captcha/send', 'post', '发送短信验证码', '验证码', ['phone']);

        // 公开 - 认证
        $this->add($p, '/api/auth/register', 'post', '手机号注册', '认证', ['phone', 'code', 'password', 'confirm_password', 'referral_code?'], 'AuthResult');
        $this->add($p, '/api/auth/login', 'post', '密码登录', '认证', ['phone', 'password'], 'AuthResult');
        $this->add($p, '/api/auth/login-by-code', 'post', '验证码登录(自动注册)', '认证', ['phone', 'code'], 'AuthResult');
        $this->add($p, '/api/auth/forget-password', 'post', '忘记密码', '认证', ['phone', 'code', 'password', 'confirm_password']);
        $this->add($p, '/api/auth/refresh', 'post', '刷新Token', '认证', [], null, ['JWT']);

        // 公开 - 微信
        $this->add($p, '/api/wechat/mini-login', 'post', '小程序登录', '微信', ['code'], 'AuthResult');
        $this->add($p, '/api/wechat/phone', 'post', '手机号绑定', '微信', ['code']);
        $this->add($p, '/api/wechat/oa-login', 'post', '公众号登录', '微信', ['code'], 'AuthResult');

        // 公开 - 公共服务
        $this->add($p, '/api/common/config', 'get', '公共配置(协议/关于/版本)', '公共服务');
        $this->add($p, '/api/common/area', 'get', '城市区域列表', '公共服务');

        // 公开 - 服务查询
        $this->add($p, '/api/service/categories', 'get', '服务分类列表', '服务');
        $this->add($p, '/api/service/items', 'get', '服务项目列表(分页/排序)', '服务');
        $this->add($p, '/api/service/detail/{id}', 'get', '服务项目详情(含规格/评价)', '服务');
        $this->add($p, '/api/service/products', 'get', '产品列表', '服务');
        $this->add($p, '/api/service/stores', 'get', '门店列表(LBS排序)', '服务');

        // 公开 - 技师查询
        $this->add($p, '/api/technician/list', 'get', '技师列表(距离排序)', '技师');
        $this->add($p, '/api/technician/detail/{id}', 'get', '技师详情(项目/评价)', '技师');
        $this->add($p, '/api/technician/schedule/{id}', 'get', '技师排班(按日期)', '技师');

        // 公开 - 内容
        $this->add($p, '/api/content/banners', 'get', '轮播图列表', '内容');
        $this->add($p, '/api/content/articles', 'get', '公告/文章列表', '内容');
        $this->add($p, '/api/content/article/{id}', 'get', '文章详情', '内容');

        // 公开 - LBS
        $this->add($p, '/api/lbs/nearby-stores', 'get', '附近门店', 'LBS');
        $this->add($p, '/api/lbs/geocode', 'get', '逆地理编码', 'LBS');

        // 用户接口 (JWT)
        $this->add($p, '/api/user/profile', 'get', '获取个人信息', '用户', [], null, ['JWT']);
        $this->add($p, '/api/user/profile', 'put', '更新个人资料(昵称/头像/性别)', '用户', ['nickname', 'avatar', 'gender'], null, ['JWT']);
        $this->add($p, '/api/user/change-password', 'post', '修改密码', '用户', ['old_password?', 'new_password', 'confirm_password'], null, ['JWT', '验证码注册用户可省略旧密码']);
        $this->add($p, '/api/user/change-phone', 'post', '换绑手机', '用户', ['old_code', 'new_phone', 'new_code'], null, ['JWT']);
        $this->add($p, '/api/user/cancel-account', 'post', '注销账号(软删除)', '用户', ['password'], null, ['JWT']);
        $this->add($p, '/api/user/logout', 'post', '退出登录(token黑名单)', '用户', [], null, ['JWT']);
        $this->add($p, '/api/user/switch-role', 'post', '身份切换(customer/technician)', '用户', ['role'], null, ['JWT', '切技师需已审核通过']);

        $this->add($p, '/api/user/addresses', 'get', '地址列表', '地址', [], null, ['JWT']);
        $this->add($p, '/api/user/addresses', 'post', '新增地址', '地址', ['contact_name', 'contact_phone', 'province', 'city', 'district', 'detail', 'is_default?'], null, ['JWT']);
        $this->add($p, '/api/user/addresses/{id}', 'get', '地址详情', '地址', [], null, ['JWT']);
        $this->add($p, '/api/user/addresses/{id}', 'put', '更新地址', '地址', [], null, ['JWT']);
        $this->add($p, '/api/user/addresses/{id}', 'delete', '删除地址', '地址', [], null, ['JWT']);

        $this->add($p, '/api/user/favorites', 'get', '收藏列表', '收藏', ['type?'], null, ['JWT']);
        $this->add($p, '/api/user/favorites', 'post', '添加收藏', '收藏', ['target_type', 'target_id'], null, ['JWT']);
        $this->add($p, '/api/user/favorites/{id}', 'delete', '取消收藏', '收藏', [], null, ['JWT']);

        $this->add($p, '/api/user/feedback', 'post', '提交反馈(内容+图片)', '反馈', ['content', 'images?'], null, ['JWT']);

        $this->add($p, '/api/user/referral', 'get', '推广信息(码/人数/积分)', '推广', [], null, ['JWT']);
        $this->add($p, '/api/user/referral/qrcode', 'get', '推广二维码(码+链接)', '推广', [], null, ['JWT']);
        $this->add($p, '/api/user/referral/referred-users', 'get', '已推荐用户列表', '推广', [], null, ['JWT']);

        // 技师接口 (JWT + 技师身份)
        $this->add($p, '/api/technician/profile', 'get', '技师档案', '技师工作台', [], null, ['JWT', '技师身份']);
        $this->add($p, '/api/technician/profile', 'put', '更新档案/入驻申请', '技师工作台', ['avatar?', 'intro?', 'real_name?', 'gender?', 'id_card?', 'id_card_front?', 'id_card_back?'], null, ['JWT', '技师身份', '首次完整填写=入驻申请']);

        $this->add($p, '/api/technician/schedule', 'get', '排班查询', '技师工作台', ['start_date?', 'end_date?'], null, ['JWT', '技师身份']);
        $this->add($p, '/api/technician/schedule', 'put', '排班设置', '技师工作台', ['date', 'time_slots'], null, ['JWT', '技师身份']);

        $this->add($p, '/api/technician/orders', 'get', '技师订单列表', '技师工作台', ['status?', 'page?'], null, ['JWT', '技师身份']);

        $this->add($p, '/api/technician/earnings', 'get', '收益概况+流水', '技师工作台', ['page?'], null, ['JWT', '技师身份']);

        $this->add($p, '/api/technician/withdraw', 'post', '申请提现', '技师工作台', ['amount'], null, ['JWT', '技师身份', '每月20号/T+1到账']);

        // 订单接口 (JWT)
        $this->add($p, '/api/order', 'post', '创建订单(锁技师3分钟)', '订单', ['order_type', 'items', 'store_id?', 'technician_id?', 'service_time?', 'coupon_id?', 'remark?'], null, ['JWT']);
        $this->add($p, '/api/order/list', 'get', '订单列表(按状态筛选)', '订单', ['status?', 'page?'], null, ['JWT']);
        $this->add($p, '/api/order/detail/{id}', 'get', '订单详情', '订单', [], null, ['JWT']);
        $this->add($p, '/api/order/cancel/{id}', 'post', '取消订单', '订单', ['reason?'], null, ['JWT']);
        $this->add($p, '/api/order/pay/{id}', 'post', '发起支付', '订单', [], null, ['JWT', '微信支付']);
        $this->add($p, '/api/order/refund/{id}', 'post', '申请退款', '订单', ['reason?'], null, ['JWT', '分级退款规则']);
        $this->add($p, '/api/order/verify/{id}', 'post', '核销(二维码)', '订单', ['code'], null, ['JWT']);

        // 营销接口 (JWT)
        $this->add($p, '/api/marketing/coupons', 'get', '我的优惠券', '营销', ['status?'], null, ['JWT']);
        $this->add($p, '/api/marketing/coupons/receive', 'post', '领取优惠券', '营销', ['coupon_id'], null, ['JWT']);
        $this->add($p, '/api/marketing/cards', 'get', '会员卡列表', '营销', [], null, ['JWT']);
        $this->add($p, '/api/marketing/cards/buy', 'post', '购买会员卡', '营销', ['card_id'], null, ['JWT']);
        $this->add($p, '/api/marketing/points', 'get', '积分流水', '营销', ['page?'], null, ['JWT']);
        $this->add($p, '/api/marketing/gift-cards', 'get', '礼品卡列表', '营销', [], null, ['JWT']);

        // 通知接口 (JWT)
        $this->add($p, '/api/notification', 'get', '通知列表', '通知', ['type?', 'page?'], null, ['JWT']);
        $this->add($p, '/api/notification/read/{id}', 'put', '标记已读', '通知', [], null, ['JWT']);
        $this->add($p, '/api/notification/read-all', 'put', '全部已读', '通知', [], null, ['JWT']);

        return $p;
    }

    private function add(array &$p, string $url, string $method, string $summary, string $tag, array $params = [], ?string $schema = null, array $notes = []): void
    {
        if (!isset($p[$url])) $p[$url] = [];

        $op = [
            'tags'    => [$tag],
            'summary' => $summary,
            'description' => $notes ? implode(' | ', $notes) : '',
            'responses' => [
                '200' => ['description' => '成功'],
                '401' => ['description' => '未认证'],
                '422' => ['description' => '参数验证失败'],
            ],
        ];

        if ($schema) {
            $op['responses']['200']['content'] = [
                'application/json' => ['schema' => ['$ref' => "#/components/schemas/{$schema}"]],
            ];
        }

        // Path params
        if (str_contains($url, '{id}')) {
            $op['parameters'][] = [
                'name' => 'id', 'in' => 'path', 'required' => true,
                'schema' => ['type' => 'string', 'description' => 'hashids编码ID'],
            ];
        }

        // Query params for GET/DELETE
        if ($params && in_array($method, ['get', 'delete'])) {
            foreach ($params as $param) {
                $clean = rtrim($param, '?');
                $required = $param === $clean;
                $op['parameters'][] = [
                    'name' => $clean, 'in' => 'query', 'required' => $required,
                    'schema' => ['type' => 'string'],
                ];
            }
        }

        // Body params for POST/PUT
        if ($params && in_array($method, ['post', 'put'])) {
            $props = [];
            $req   = [];
            foreach ($params as $param) {
                $clean = rtrim($param, '?');
                $props[$clean] = ['type' => 'string'];
                if ($param === $clean) $req[] = $clean;
            }
            $op['requestBody'] = [
                'required' => true,
                'content' => ['application/json' => ['schema' => [
                    'type' => 'object', 'properties' => $props, 'required' => $req,
                ]]],
            ];
        }

        $p[$url][$method] = $op;
    }
}
