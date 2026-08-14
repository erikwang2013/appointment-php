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
  }
});
