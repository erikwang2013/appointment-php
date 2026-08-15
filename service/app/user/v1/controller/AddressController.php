<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\UserAddress;
use Webman\Http\Request;

/**
 * 用户收货地址控制器
 * 地址的增删改查
 */
class AddressController extends BaseController
{
    /**
     * 获取用户地址列表
     * GET /api/user/addresses
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;

        $addresses = UserAddress::where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get();

        return $this->success($addresses);
    }

    /**
     * 新增收货地址
     * POST /api/user/addresses
     */
    public function store(Request $request)
    {
        $userId = $request->user_id;

        $contactName = $request->input('contact_name', '');
        $contactPhone = $request->input('contact_phone', '');
        $province = $request->input('province', '');
        $city = $request->input('city', '');
        $district = $request->input('district', '');
        $detail = $request->input('detail', '');
        $lat = $request->input('lat', '');
        $lng = $request->input('lng', '');
        $isDefault = (int)$request->input('is_default', 0);

        if (empty($contactName) || empty($contactPhone) || empty($province) || empty($city) || empty($district) || empty($detail)) {
            return $this->error('请填写完整地址信息');
        }

        if ($isDefault) {
            // 取消其他默认地址
            UserAddress::where('user_id', $userId)
                ->where('is_default', 1)
                ->update(['is_default' => 0]);
        }

        $address = UserAddress::create([
            'id' => UserAddress::generateId(),
            'user_id' => $userId,
            'contact_name' => $contactName,
            'contact_phone' => $contactPhone,
            'province' => $province,
            'city' => $city,
            'district' => $district,
            'detail' => $detail,
            'lat' => $lat ?: null,
            'lng' => $lng ?: null,
            'is_default' => $isDefault,
        ]);

        return $this->success($address, '地址添加成功');
    }

    /**
     * 获取单个地址详情
     * GET /api/user/addresses/{id}
     */
    public function show(Request $request, string $id)
    {
        $userId = $request->user_id;

        $address = UserAddress::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$address) {
            return $this->error('地址不存在', 404);
        }

        return $this->success($address);
    }

    /**
     * 更新收货地址
     * PUT /api/user/addresses/{id}
     */
    public function update(Request $request, string $id)
    {
        $userId = $request->user_id;

        $address = UserAddress::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$address) {
            return $this->error('地址不存在', 404);
        }

        $data = [];
        $fields = ['contact_name', 'contact_phone', 'province', 'city', 'district', 'detail'];

        foreach ($fields as $field) {
            if ($request->input($field) !== null) {
                $data[$field] = trim($request->input($field, ''));
            }
        }

        if ($request->input('lat') !== null) {
            $data['lat'] = $request->input('lat') ?: null;
        }

        if ($request->input('lng') !== null) {
            $data['lng'] = $request->input('lng') ?: null;
        }

        if ($request->input('is_default') !== null) {
            $isDefault = (int)$request->input('is_default', 0);
            $data['is_default'] = $isDefault;

            if ($isDefault) {
                // 取消其他默认地址
                UserAddress::where('user_id', $userId)
                    ->where('is_default', 1)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => 0]);
            }
        }

        if (!empty($data)) {
            $address->fill($data);
            $address->save();
        }

        return $this->success($address, '地址更新成功');
    }

    /**
     * 删除收货地址
     * DELETE /api/user/addresses/{id}
     */
    public function destroy(Request $request, string $id)
    {
        $userId = $request->user_id;

        $address = UserAddress::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$address) {
            return $this->error('地址不存在', 404);
        }

        $address->delete();

        return $this->success(null, '地址已删除');
    }
}
