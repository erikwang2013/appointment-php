<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Service;
use app\model\Product;
use app\model\SystemConfig;
use support\Request;
use support\Response;
use Illuminate\Support\Facades\DB;

/**
 * 卡项设计控制器
 * 管理服务套餐/组合卡的设计与定价
 */
class ServiceCardController extends BaseController
{
    /**
     * 卡项设计列表
     * 从 erik_system_config 中读取 card_designs JSON 配置
     */
    public function index(Request $request): Response
    {
        $page  = (int) $request->input('page', 1);
        $limit = (int) $request->input('limit', 15);
        $keyword = $request->input('keyword', '');

        $config = SystemConfig::where('group', 'card')
            ->where('key', 'card_designs')
            ->first();

        $cards = [];
        if ($config && !empty($config->value)) {
            $raw = json_decode($config->value, true);
            if (is_array($raw)) {
                $cards = $raw;
            }
        }

        // 关键词筛选
        if ($keyword) {
            $cards = array_filter($cards, function ($card) use ($keyword) {
                return str_contains($card['name'] ?? '', $keyword)
                    || str_contains($card['type'] ?? '', $keyword);
            });
        }

        // 分页
        $total = count($cards);
        $cards = array_values($cards);
        $list = array_slice($cards, ($page - 1) * $limit, $limit);

        // 为每个卡项附加服务/产品详细信息
        $list = array_map(function ($card) {
            if (isset($card['services']) && is_array($card['services'])) {
                $serviceIds = array_column($card['services'], 'service_id');
                $services = Service::whereIn('id', $serviceIds)->get()->keyBy('id');
                foreach ($card['services'] as &$svc) {
                    $svc['service_detail'] = $services[$svc['service_id']] ?? null;
                }
            }
            if (isset($card['product_ids']) && is_array($card['product_ids'])) {
                $productIds = array_column($card['product_ids'], 'product_id');
                $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
                foreach ($card['product_ids'] as &$prod) {
                    $prod['product_detail'] = $products[$prod['product_id']] ?? null;
                }
            }
            return $card;
        }, $list);

        return $this->success([
            'list'  => array_values($list),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 创建卡项设计
     * @Apidoc\Param("name", type="string", require=true, desc="卡项名称")
     * @Apidoc\Param("type", type="string", require=true, desc="卡项类型: package/combo")
     * @Apidoc\Param("services", type="array", require=false, desc="内含服务 [{service_id, times, handwork_fee}]")
     * @Apidoc\Param("product_ids", type="array", require=false, desc="内含产品 [{product_id, qty}]")
     * @Apidoc\Param("total_price", type="float", require=true, desc="总售价")
     * @Apidoc\Param("handwork_total", type="float", require=false, desc="手工费总额")
     * @Apidoc\Param("commission_amount", type="float", require=false, desc="佣金金额")
     * @Apidoc\Param("sales_commission", type="float", require=false, desc="销售提成")
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'name'        => 'required|string|max:100',
            'type'        => 'required|string|in:package,combo',
            'total_price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $services = $request->input('services', []);
        $productIds = $request->input('product_ids', []);

        $cardId = (string) $this->generateId();
        $card = [
            'id'                => $cardId,
            'name'              => $request->input('name'),
            'type'              => $request->input('type'),
            'services'          => $services,
            'product_ids'       => $productIds,
            'total_price'       => (float) $request->input('total_price'),
            'handwork_total'    => (float) $request->input('handwork_total', 0),
            'commission_amount' => (float) $request->input('commission_amount', 0),
            'sales_commission'  => (float) $request->input('sales_commission', 0),
            'status'            => (int) $request->input('status', 1),
            'description'       => $request->input('description', ''),
            'created_at'        => date('Y-m-d H:i:s'),
            'updated_at'        => date('Y-m-d H:i:s'),
        ];

        // 保存到 erik_system_config
        $this->saveCardDesigns($card);

        return $this->success($card, '创建成功');
    }

    /**
     * 卡项详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id = $this->decodeId($hashid);
        $card = $this->findCardById((string) $id);

        if (!$card) {
            return $this->fail('卡项不存在', 404);
        }

        // 附加服务详情
        if (!empty($card['services'])) {
            $serviceIds = array_column($card['services'], 'service_id');
            $services = Service::whereIn('id', $serviceIds)->get()->keyBy('id');
            foreach ($card['services'] as &$svc) {
                $svc['service_detail'] = $services[$svc['service_id']] ?? null;
            }
        }

        // 附加产品详情
        if (!empty($card['product_ids'])) {
            $productIds = array_column($card['product_ids'], 'product_id');
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
            foreach ($card['product_ids'] as &$prod) {
                $prod['product_detail'] = $products[$prod['product_id']] ?? null;
            }
        }

        return $this->success($card);
    }

    /**
     * 更新卡项设计
     */
    public function update(Request $request, string $hashid): Response
    {
        $id = $this->decodeId($hashid);
        $card = $this->findCardById((string) $id);

        if (!$card) {
            return $this->fail('卡项不存在', 404);
        }

        $fillable = [
            'name', 'type', 'services', 'product_ids',
            'total_price', 'handwork_total', 'commission_amount',
            'sales_commission', 'status', 'description',
        ];

        foreach ($fillable as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                if (in_array($field, ['total_price', 'handwork_total', 'commission_amount', 'sales_commission'])) {
                    $value = (float) $value;
                } elseif ($field === 'status') {
                    $value = (int) $value;
                }
                $card[$field] = $value;
            }
        }
        $card['updated_at'] = date('Y-m-d H:i:s');

        $this->updateCardDesigns($card);

        return $this->success($card, '更新成功');
    }

    /**
     * 删除卡项
     */
    public function destroy(Request $request, string $hashid): Response
    {

        $id = $this->decodeId($hashid);
        $card = $this->findCardById((string) $id);

        if (!$card) {
            return $this->fail('卡项不存在', 404);
        }

        $this->deleteCardDesign((string) $id);

        return $this->success([], '删除成功');
    }

    // ── 内部辅助方法 ──

    /**
     * 从 erik_system_config 读取全部卡项
     */
    private function getCardDesigns(): array
    {
        $config = SystemConfig::where('group', 'card')
            ->where('key', 'card_designs')
            ->first();

        if ($config && !empty($config->value)) {
            $cards = json_decode($config->value, true);
            return is_array($cards) ? $cards : [];
        }

        return [];
    }

    /**
     * 按ID查找卡项
     */
    private function findCardById(string $id): ?array
    {
        $cards = $this->getCardDesigns();
        foreach ($cards as $card) {
            if (($card['id'] ?? '') === $id) {
                return $card;
            }
        }
        return null;
    }

    /**
     * 保存新卡项到配置
     */
    private function saveCardDesigns(array $card): void
    {
        $cards = $this->getCardDesigns();
        $cards[] = $card;

        $this->persistCardDesigns($cards);
    }

    /**
     * 更新卡项配置
     */
    private function updateCardDesigns(array $card): void
    {
        $cards = $this->getCardDesigns();
        foreach ($cards as &$existing) {
            if (($existing['id'] ?? '') === $card['id']) {
                $existing = $card;
                break;
            }
        }
        $this->persistCardDesigns($cards);
    }

    /**
     * 删除卡项
     */
    private function deleteCardDesign(string $id): void
    {
        $cards = $this->getCardDesigns();
        $cards = array_filter($cards, function ($card) use ($id) {
            return ($card['id'] ?? '') !== $id;
        });
        $this->persistCardDesigns(array_values($cards));
    }

    /**
     * 持久化卡项配置到 erik_system_config
     */
    private function persistCardDesigns(array $cards): void
    {
        $config = SystemConfig::where('group', 'card')
            ->where('key', 'card_designs')
            ->first();

        if ($config) {
            $config->value = json_encode($cards, JSON_UNESCAPED_UNICODE);
            $config->save();
        } else {
            $config = new SystemConfig();
            $config->id = $this->generateId();
            $config->group = 'card';
            $config->key = 'card_designs';
            $config->value = json_encode($cards, JSON_UNESCAPED_UNICODE);
            $config->type = 'json';
            $config->description = '卡项设计配置（服务套餐/组合卡）';
            $config->save();
        }
    }
}
