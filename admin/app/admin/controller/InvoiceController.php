<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\admin\controller;

use app\model\Invoice;
use InvalidArgumentException;
use support\Request;
use support\Response;

/**
 * 电子发票管理控制器
 *
 * GET  /admin/invoices             列表（?status=&user_id=&page=&limit=）
 * POST /admin/invoices/{id}/issue  开票（body: issued_no 必填）pending → issued
 * POST /admin/invoices/{id}/reject 驳回（body: remark 必填）  pending → rejected
 *
 * 非 pending 状态不可开票/驳回（422）；权限：382 列表查看、383 开票、384 驳回。
 */
class InvoiceController extends BaseController
{
    /**
     * 发票列表
     * GET /admin/invoices?page&limit&status&user_id
     */
    public function index(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $status  = (string) $request->input('status', '');
        $userId  = (string) $request->input('user_id', '');

        $query = Invoice::with('user');

        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($userId !== '') {
            $query->where('user_id', $userId);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('created_at', 'desc')
                       ->get()
                       ->map(fn($i) => $i->toArray());

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 开票
     * POST /admin/invoices/{id}/issue  body: {issued_no}
     */
    public function issue(Request $request, string $hashid): Response
    {
        $invoice = $this->findInvoice($hashid);
        if ($invoice instanceof Response) {
            return $invoice;
        }
        if ($invoice->status !== Invoice::STATUS_PENDING) {
            return $this->fail('仅待开票状态可开票', 422);
        }

        $issuedNo = trim((string) $request->input('issued_no', ''));
        if ($issuedNo === '') {
            return $this->fail('发票号必填', 422);
        }

        $invoice->status    = Invoice::STATUS_ISSUED;
        $invoice->issued_no = $issuedNo;
        $invoice->issued_at = date('Y-m-d H:i:s');
        $invoice->save();

        return $this->success($invoice->toArray(), '开票成功');
    }

    /**
     * 驳回
     * POST /admin/invoices/{id}/reject  body: {remark}
     */
    public function reject(Request $request, string $hashid): Response
    {
        $invoice = $this->findInvoice($hashid);
        if ($invoice instanceof Response) {
            return $invoice;
        }
        if ($invoice->status !== Invoice::STATUS_PENDING) {
            return $this->fail('仅待开票状态可驳回', 422);
        }

        $remark = trim((string) $request->input('remark', ''));
        if ($remark === '') {
            return $this->fail('驳回必须填写原因', 422);
        }

        $invoice->status = Invoice::STATUS_REJECTED;
        $invoice->remark = $remark;
        $invoice->save();

        return $this->success($invoice->toArray(), '已驳回');
    }

    /** 解析 hashid 并加载发票，失败返回 422/404 响应 */
    private function findInvoice(string $hashid): Invoice|Response
    {
        try {
            $id = $this->decodeId($hashid);
        } catch (InvalidArgumentException) {
            return $this->fail('无效的发票ID', 422);
        }

        $invoice = Invoice::find($id);
        if (!$invoice) {
            return $this->fail('发票记录不存在', 404);
        }

        return $invoice;
    }
}
