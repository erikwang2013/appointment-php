const api = require('../../utils/api');
Page({
  data: { userInfo: null, unreadCount: 0 },
  onShow() { this.loadProfile(); },
  async loadProfile() {
    try { const res = await api.authGet('/user/profile'); this.setData({ userInfo: res.data }); }
    catch(e) { /* not logged in */ }
  },
  onSwitchRole() { const role = this.data.userInfo?.active_role === 'customer' ? 'technician' : 'customer'; api.authPost('/user/switch-role', { role }).then(() => this.loadProfile()); },
  onMessage() { wx.navigateTo({ url: '/pages/message/index' }); },
  onNav(e) { const url = e.currentTarget.dataset.url; if (url) wx.navigateTo({ url }); }
});
