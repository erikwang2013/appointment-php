const api = require('../../utils/api');

Page({
  data: { cards: [], busy: false },

  onShow() {
    this.loadCards();
  },

  async loadCards() {
    try {
      const res = await api.authGet('/marketing/cards/my');
      this.setData({ cards: res.data || [] });
    } catch (e) {
      wx.showToast({ title: (e && e.message) || '加载失败', icon: 'none' });
    }
  },

  onUse(e) {
    if (this.data.busy) return;
    const idx = e.currentTarget.dataset.index;
    const card = this.data.cards[idx];
    if (!card) return;
    this.chooseService(card);
  },

  async chooseService(card) {
    try {
      const res = await api.get('/service/items', { per_page: 50 });
      const list = Array.isArray(res.data) ? res.data : [];
      if (!list.length) {
        wx.showToast({ title: '暂无可选服务', icon: 'none' });
        return;
      }
      wx.showActionSheet({
        itemList: list.map((s) => s.name),
        success: (r) => this.confirmUse(card, list[r.tapIndex]),
        fail: () => {}
      });
    } catch (err) {
      wx.showToast({ title: '服务加载失败', icon: 'none' });
    }
  },

  confirmUse(card, service) {
    wx.showModal({
      title: '确认核销',
      content: `使用「${card.name}」核销服务「${service.name}」，将扣除 1 次，确认？`,
      success: (r) => {
        if (!r.confirm) return;
        this.submitUse(card, service);
      }
    });
  },

  async submitUse(card, service) {
    if (this.data.busy) return;
    this.setData({ busy: true });
    try {
      const res = await api.authPost('/marketing/cards/use', {
        user_card_id: card.id,
        service_id: service.id,
        remark: '小程序次卡核销'
      });
      wx.showModal({
        title: '核销成功',
        content: `已核销，次卡剩余 ${res.data.remaining_times} 次`,
        showCancel: false,
        success: () => this.loadCards()
      });
    } catch (e) {
      wx.showToast({ title: (e && e.message) || '核销失败', icon: 'none' });
      this.loadCards();
    } finally {
      this.setData({ busy: false });
    }
  }
});
