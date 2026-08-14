const api = require('../../utils/api');
const REFUND_STATUS_TEXT = { pending: '退款处理中', success: '已退款', failed: '退款失败' };
Page({
  data: { orders: [], tabs: ['全部','待支付','已支付','已完成','已取消'], activeTab: 0, statusMap: ['','pending','paid','completed','cancelled'], page: 1, perPage: 10, hasMore: true },
  onShow() { this.loadOrders(true); },
  async loadOrders(reset) {
    if (reset) this.setData({ page: 1, orders: [], hasMore: true });
    if (this._loading || !this.data.hasMore) return;
    const { page, perPage, activeTab, statusMap } = this.data;
    const status = statusMap[activeTab];
    // 契约对齐：服务端 OrderController::index 读 page / per_page，返回 meta.has_more
    const data = { page, per_page: perPage };
    if (status) data.status = status;
    this._loading = true;
    try {
      const res = await api.authGet('/order/list', data);
      const list = (res.data || []).map(o => ({ ...o, refund_status_text: REFUND_STATUS_TEXT[o.refund_status] || '' }));
      const hasMore = res.meta ? !!res.meta.has_more : list.length >= perPage;
      this.setData({ orders: page === 1 ? list : [...this.data.orders, ...list], hasMore, page: hasMore ? page + 1 : page });
    } catch(e) { wx.showToast({title: '加载失败', icon: 'none'}); }
    finally { this._loading = false; }
  },
  onTabChange(e) { this.setData({ activeTab: e.currentTarget.dataset.index }); this.loadOrders(true); },
  onReachBottom() { if (this.data.hasMore) this.loadOrders(); },
  onDetail(e) { wx.navigateTo({ url: '/pages/order/detail?id=' + e.currentTarget.dataset.id }); },
  onPay(e) { /* call /api/order/pay/{id} */ },
  onCancel(e) { /* call /api/order/cancel/{id} */ }
});
