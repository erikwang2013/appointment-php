const api = require('../../utils/api');
Page({
  data: { orders: [], tabs: ['全部','待支付','已支付','已完成','已取消'], activeTab: 0, statusMap: ['','pending','paid','completed','cancelled'] },
  onShow() { this.loadOrders(); },
  async loadOrders() {
    const status = this.data.statusMap[this.data.activeTab];
    try { const res = await api.authGet('/order/list' + (status ? '?status='+status : '')); this.setData({ orders: res.data || [] }); }
    catch(e) { wx.showToast({title: '加载失败', icon: 'none'}); }
  },
  onTabChange(e) { this.setData({ activeTab: e.currentTarget.dataset.index }); this.loadOrders(); },
  onDetail(e) { wx.navigateTo({ url: '/pages/order/detail?id=' + e.currentTarget.dataset.id }); },
  onPay(e) { /* call /api/order/pay/{id} */ },
  onCancel(e) { /* call /api/order/cancel/{id} */ }
});
