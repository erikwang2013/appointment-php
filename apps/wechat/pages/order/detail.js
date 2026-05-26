const api = require('../../utils/api');
Page({
  data: { order: null },
  onLoad(options) { this.loadDetail(options.id); },
  async loadDetail(id) {
    try { const res = await api.authGet('/order/detail/' + id); this.setData({ order: res.data }); }
    catch(e) { wx.showToast({title: '加载失败', icon: 'none'}); }
  },
  onPay() { /* POST /api/order/pay/{id} */ },
  onCancel() { /* POST /api/order/cancel/{id} */ },
  onRefund() { /* POST /api/order/refund/{id} */ }
});
