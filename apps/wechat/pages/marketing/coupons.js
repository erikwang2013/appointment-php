const api = require('../../utils/api');
Page({
  data: { coupons: [], activeTab: 0 },
  onShow() { this.loadCoupons(); },
  async loadCoupons() {
    const status = ['available','used','expired'][this.data.activeTab];
    try { const res = await api.authGet('/marketing/coupons?status='+status); this.setData({ coupons: res.data || [] }); }
    catch(e) { wx.showToast({title: '加载失败', icon: 'none'}); }
  },
  onTabChange(e) { this.setData({ activeTab: e.currentTarget.dataset.index }); this.loadCoupons(); }
});
