const api = require('../../utils/api');
Page({
  data: { earnings: { today_income: '0.00', pending: '0.00', balance: '0.00' }, todayOrders: 0 },
  onShow() { this.loadData(); },
  async loadData() {
    try {
      const [earnRes, orderRes] = await Promise.all([api.authGet('/technician/earnings'), api.authGet('/technician/orders?page=1')]);
      const summary = (earnRes.data && earnRes.data.summary) || {};
      this.setData({
        earnings: {
          today_income: summary.today_income || '0.00',
          pending: summary.pending_settlement || '0.00',
          balance: summary.balance || '0.00'
        },
        todayOrders: (orderRes.data || []).length
      });
    } catch(e) { /* not technician */ }
  },
  // 菜单入口（扫码核销等）
  onMenuTap(e) {
    const action = e.currentTarget.dataset.action;
    if (action === 'scanVerify') {
      this.scanToVerify();
    }
  },
  // 扫码核销：扫描用户出示的订单核销码 → 调服务端核销
  scanToVerify() {
    wx.scanCode({
      onlyFromCamera: true,
      scanType: ['qrCode', 'barCode'],
      success: (res) => {
        const code = (res.result || '').trim();
        if (!code) {
          wx.showToast({ title: '未识别到核销码', icon: 'none' });
          return;
        }
        wx.showLoading({ title: '核销中...' });
        api.authPost('/order/verify-by-code', { code })
          .then((resp) => {
            wx.hideLoading();
            const data = resp.data || {};
            const orderNo = data.order_no || '';
            if (data.already_verified) {
              wx.showModal({
                title: '已核销',
                content: '订单 ' + orderNo + ' 此前已核销，请勿重复核销',
                showCancel: false
              });
            } else {
              wx.showModal({
                title: '核销成功',
                content: '订单 ' + orderNo + ' 已开始服务',
                showCancel: false
              });
            }
            this.loadData();
          })
          .catch((err) => {
            wx.hideLoading();
            const msg = (err && (err.message || err.msg)) || '核销失败';
            wx.showModal({ title: '核销失败', content: msg, showCancel: false });
          });
      },
      fail: () => { /* 用户取消扫码 */ }
    });
  }
});
