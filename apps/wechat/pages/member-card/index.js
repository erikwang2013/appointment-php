const api = require('../../utils/api');

Page({
  data: { cards: [], balance: 0, busy: false },

  onShow() {
    this.loadCards();
    this.loadBalance();
  },

  async loadCards() {
    try {
      const res = await api.authGet('/marketing/member-cards');
      this.setData({ cards: res.data || [] });
    } catch (e) {
      wx.showToast({ title: (e && e.message) || '加载失败', icon: 'none' });
    }
  },

  async loadBalance() {
    try {
      const res = await api.authGet('/wallet');
      this.setData({ balance: Number(res.data && res.data.balance) || 0 });
    } catch (e) {
      // 余额展示失败不阻塞购买
    }
  },

  onBuy(e) {
    if (this.data.busy) return;
    const idx = e.currentTarget.dataset.index;
    const card = this.data.cards[idx];
    if (!card) return;
    const price = Number(card.price) || 0;
    if (this.data.balance < price) {
      wx.showModal({
        title: '余额不足',
        content: `购买「${card.name}」需 ¥${price.toFixed(2)}，当前余额 ¥${this.data.balance.toFixed(2)}，请先充值`,
        showCancel: false
      });
      return;
    }
    wx.showModal({
      title: '确认购买',
      content: `「${card.name}」¥${price.toFixed(2)}，将从余额扣除，确认购买？`,
      success: (r) => {
        if (r.confirm) this.submitBuy(card);
      }
    });
  },

  async submitBuy(card) {
    if (this.data.busy) return;
    this.setData({ busy: true });
    try {
      await api.authPost('/marketing/member-cards/buy', { card_id: card.id });
      wx.showToast({ title: '购买成功', icon: 'success' });
      this.loadCards();
      this.loadBalance();
    } catch (e) {
      wx.showToast({ title: (e && e.message) || '购买失败', icon: 'none' });
    } finally {
      this.setData({ busy: false });
    }
  }
});
