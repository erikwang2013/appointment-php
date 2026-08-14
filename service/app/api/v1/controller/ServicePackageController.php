<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\Service;
use app\model\ServicePackage;
use app\model\Order;
use app\model\OrderItem;
use app\model\OrderPayment;
use app\model\OrderVerification;
use support\Db;
use support\Log;
use support\Redis;
use Webman\Http\Request;

/**
 * 服务套餐控制器
 * 套餐浏览、详情、购买
 */
class ServicePackageController extends BaseController
{
    /**
     * 套餐列表
     * GET /api/service-packages
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function index(Request $request)
    {
        $page = (int)($request->input('page', 1));
        $perPage = (int)($request->input('per_page', 15));

        // Redis 缓存 5 分钟（读多写少，按参数哈希分键）
        $cacheKey = 'svc:package:index:' . md5(json_encode([$page, $perPage]));
        $cached = Redis::get($cacheKey);
        if ($cached !== null && $cached !== false) {
            return json(json_decode($cached, true));
        }

        $query = ServicePackage::where('status', 1)
            ->orderBy('sort', 'desc')
            ->orderBy('id', 'desc');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $response = $this->paginate($paginator);
        Redis::setex($cacheKey, 300, $response->rawBody());
        return $response;
    }

    /**
     * 套餐详情（含包含的服务信息）
     * GET /api/service-packages/{id}
     *
     * @param mixed $id
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function show($id, Request $request)
    {
        $decodedId = $this->decodeId((string)$id);

        if ($decodedId === null) {
            return $this->error('套餐不存在');
        }

        $package = ServicePackage::where('status', 1)->find($decodedId);

        if (!$package) {
            return $this->error('套餐不存在');
        }

        // 解析套餐包含的服务并加载详情
        $services = $package->services ?? [];
        $servicesDetail = [];

        if (is_array($services)) {
            foreach ($services as $svc) {
                $serviceId = $svc['service_id'] ?? null;
                if ($serviceId) {
                    $service = Service::find($serviceId);
                    if ($service) {
                        $servicesDetail[] = [
                            'id' => $service->id,
                            'name' => $service->name,
                            'cover_image' => $service->cover_image,
                            'price' => $service->price,
                            'duration' => $service->duration,
                            'times' => $svc['times'] ?? 1,
                        ];
                    }
                }
            }
        }

        $data = $package->toArray();
        $data['services_detail'] = $servicesDetail;

        return $this->success($data);
    }

    /**
     * 购买套餐
     * POST /api/service-packages/buy
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function buy(Request $request)
    {
        $userId = $request->user_id;
        $packageId = $this->decodeId($request->input('package_id'));

        if (!$packageId) {
            return $this->error('请选择套餐');
        }

        $package = ServicePackage::where('status', 1)->find($packageId);

        if (!$package) {
            return $this->error('套餐不存在或已下架');
        }

        $totalAmount = $package->price;
        $paidAmount = $totalAmount;
        $orderNo = generate_order_no();

        Db::beginTransaction();
        try {
            $order = Order::create([
                'id'              => Order::generateId(),
                'order_no'        => $orderNo,
                'user_id'         => $userId,
                'order_type'      => 'package',
                'total_amount'    => $totalAmount,
                'discount_amount' => 0.00,
                'paid_amount'     => $paidAmount,
                'status'          => Order::STATUS_PENDING,
            ]);

            // 创建订单明细
            OrderItem::create([
                'id'          => OrderItem::generateId(),
                'order_id'    => $order->id,
                'target_type' => 'package',
                'target_id'   => $package->id,
                'name'        => $package->name,
                'cover_image' => $package->cover_image ?? '',
                'price'       => $package->price,
                'quantity'    => 1,
            ]);

            // 创建支付记录
            OrderPayment::create([
                'id'         => OrderPayment::generateId(),
                'order_id'   => $order->id,
                'payment_no' => OrderPayment::generatePaymentNo(),
                'pay_type'   => 'wechat',
                'amount'     => $paidAmount,
                'status'     => OrderPayment::STATUS_PENDING,
            ]);

            // 累积销量
            $package->increment('sales_volume');

            Db::commit();

            $order->load(['items', 'payment']);

            return $this->success($order, '套餐订单创建成功');
        } catch (\Throwable $e) {
            Db::rollBack();
            // M3: 内部异常详情仅记日志，对外返回通用文案
            Log::error('[ServicePackageController] package purchase failed: ' . $e->getMessage());
            return $this->error('套餐购买失败，请稍后重试');
        }
    }
}
