const api = require('../../utils/api');

const STATUS_TEXT = { used: '已兑换', expired: '已过期' };

Page({
  data: {
    cards: [],
    code: '',
    redeeming: false
  },

  onShow() { this.load(); },

  async load() {
    try {
      const res = await api.authGet('/gift-cards/my');
      const items = (res.data || []).map(c => ({
        id: c.id,
        type: c.type,
        amount: c.type === 'cash' ? Number(c.amount).toFixed(2) : '',
        gift_name: c.gift_name || '',
        status_text: STATUS_TEXT[c.status] || c.status,
        used_at: (c.used_at || '').slice(0, 16).replace('T', ' ')
      }));
      this.setData({ cards: items });
    } catch (e) { wx.showToast({ title: '礼品卡加载失败', icon: 'none' }); }
  },

  onCodeInput(e) { this.setData({ code: e.detail.value }); },

  async onRedeem() {
    const code = (this.data.code || '').trim().toUpperCase();
    if (!code) {
      wx.showToast({ title: '请输入兑换码', icon: 'none' });
      return;
    }
    this.setData({ redeeming: true });
    try {
      const res = await api.authPost('/gift-cards/redeem', { code });
      wx.showToast({ title: res.message || '兑换成功', icon: 'success' });
      this.setData({ code: '' });
      this.load();
    } catch (e) {
      wx.showToast({ title: (e && e.message) || '兑换失败，请重试', icon: 'none' });
    } finally {
      this.setData({ redeeming: false });
    }
  }
});
