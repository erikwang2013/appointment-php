<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\AdminUser;
use app\model\AdminRole;
use app\model\Store;
use support\Request;
use support\Response;

class StoreManagerController extends BaseController
{
    /**
     * 店长列表
     * 搜索: username/phone/store
     */
    public function index(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $keyword = $request->input('keyword', '');
        $storeId = $request->input('store_id', '');

        $query = AdminUser::with('roles');

        // 仅查询有 store_id > 0 的店长子账号
        $query->where('store_id', '>', 0);

        if ($keyword) {
            $query->where(function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }
        if ($storeId !== null && $storeId !== '') {
            $query->where('store_id', (int) $storeId);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('id', 'desc')
                       ->get()
                       ->map(function ($user) {
                           $data = $user->toArray();
                           unset($data['password'], $data['id_card']);
                           // 脱敏
                           if (!empty($data['phone'])) {
                               $data['phone'] = preg_replace('/^(\d{3})\d+(\d{4})$/', '$1****$2', $data['phone']);
                           }
                           // 关联门店名称
                           if (!empty($data['store_id'])) {
                               $store = Store::find($data['store_id']);
                               $data['store_name'] = $store->name ?? '';
                           }
                           return $this->encodeIds($data);
                       });

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 创建店长子账号
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'username'  => 'required|string|min:3|max:50',
            'password'  => 'required|string|min:6|max:32',
            'real_name' => 'required|string|max:50',
            'store_id'  => 'required|integer|min:1',
            'role_id'   => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $exists = AdminUser::where('username', $request->input('username'))->exists();
        if ($exists) {
            return $this->fail(trans('messages.username_exists'), 422);
        }

        // 验证门店存在
        $store = Store::find((string) $request->input('store_id'));
        if (!$store) {
            return $this->fail('门店不存在', 404);
        }

        // 验证角色存在
        $roleId = (int) $request->input('role_id');
        $role = AdminRole::find($roleId);
        if (!$role) {
            return $this->fail('角色不存在', 404);
        }

        $user = new AdminUser();
        $user->id       = $this->generateId();
        $user->username = $request->input('username');
        $user->password = password_hash($request->input('password'), PASSWORD_BCRYPT);
        $user->real_name = $request->input('real_name');
        $user->store_id = (int) $request->input('store_id');
        $user->phone    = $request->input('phone', '');
        $user->email    = $request->input('email', '');
        $user->status   = (int) $request->input('status', 1);
        $user->save();

        // 关联角色
        $user->roles()->attach($roleId);

        $data = $user->toArray();
        unset($data['password'], $data['id_card']);
        $data['role_id'] = $roleId;
        $data['role_name'] = $role->name;

        return $this->success($this->encodeIds($data), trans('messages.create_success'));
    }

    /**
     * 更新店长信息
     */
    public function update(Request $request, string $hashid): Response
    {
        $id   = $this->decodeId($hashid);
        $user = AdminUser::find($id);
        if (!$user) {
            return $this->fail(trans('messages.user_not_found'), 404);
        }

        if ($request->has('username')) {
            $exists = AdminUser::where('username', $request->input('username'))
                ->where('id', '!=', $id)->exists();
            if ($exists) {
                return $this->fail(trans('messages.username_exists'), 422);
            }
            $user->username = $request->input('username');
        }
        if ($request->has('real_name')) {
            $user->real_name = $request->input('real_name');
        }
        if ($request->has('password') && !empty($request->input('password'))) {
            $user->password = password_hash($request->input('password'), PASSWORD_BCRYPT);
        }
        if ($request->has('phone')) {
            $user->phone = $request->input('phone', '');
        }
        if ($request->has('email')) {
            $user->email = $request->input('email', '');
        }
        if ($request->has('store_id')) {
            $user->store_id = (int) $request->input('store_id');
        }
        if ($request->has('status')) {
            $user->status = (int) $request->input('status');
        }
        $user->save();

        // 更新角色
        if ($request->has('role_id')) {
            $user->roles()->sync([(int) $request->input('role_id')]);
        }

        $data = $user->toArray();
        unset($data['password'], $data['id_card']);
        $data['roles'] = $user->roles()->get()->toArray();

        return $this->success($this->encodeIds($data), trans('messages.update_success'));
    }

    /**
     * 删除店长
     */
    public function destroy(Request $request, string $hashid): Response
    {

        $id   = $this->decodeId($hashid);
        $user = AdminUser::find($id);
        if (!$user) {
            return $this->fail(trans('messages.user_not_found'), 404);
        }

        $user->roles()->detach();
        $user->delete();

        return $this->success([], trans('messages.delete_success'));
    }
}
