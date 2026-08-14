const api = require('../../utils/api');
const REFUND_STATUS_TEXT = { pending: '退款处理中', success: '已退款', failed: '退款失败' };
Page({
  data: { order: null },
  onLoad(options) { this.loadDetail(options.id); },
  async loadDetail(id) {
    try {
      const res = await api.authGet('/order/detail/' + id);
      const order = res.data;
      if (order) order.refund_status_text = REFUND_STATUS_TEXT[order.refund_status] || '';
      this.setData({ order });
    }
    catch(e) { wx.showToast({title: '加载失败', icon: 'none'}); }
  },
  onPay() { /* POST /api/order/pay/{id} */ },
  onCancel() { /* POST /api/order/cancel/{id} */ },
  onRefund() { /* POST /api/order/refund/{id} */ }
});
