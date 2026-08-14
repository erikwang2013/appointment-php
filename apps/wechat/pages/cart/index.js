const api = require('../../utils/api');
Page({
  data: { items: [], totalAmount: '0.00', allSelected: true },
  onShow() { this.loadCart(); },
  async loadCart() {
    try {
      const res = await api.authGet('/order/cart');
      const items = (res.data || []).map(i => ({ ...i, selected: i.selected !== false }));
      this.setData({ items });
      this.calcTotal();
    } catch(e) { wx.showToast({title: '加载失败', icon: 'none'}); }
  },
  saveCart() {
    // 本地变更后整体回写 Redis 购物车（/order/cart POST，服务端按 items 规范化存储）
    api.authPost('/order/cart', { items: this.data.items }).catch(() => {});
  },
  calcTotal() { let total = 0; this.data.items.filter(i => i.selected).forEach(i => total += i.price * i.quantity); this.setData({ totalAmount: total.toFixed(2) }); },
  onQuantityChange(e) { const { index, type } = e.currentTarget.dataset; const items = this.data.items; if (type === 'plus') items[index].quantity++; else if (items[index].quantity > 1) items[index].quantity--; this.setData({ items }); this.calcTotal(); this.saveCart(); },
  onSelect(e) { const { index } = e.currentTarget.dataset; this.data.items[index].selected = !this.data.items[index].selected; this.setData({ items: this.data.items }); this.calcTotal(); },
  onDelete(e) { const { index } = e.currentTarget.dataset; this.data.items.splice(index, 1); this.setData({ items: this.data.items }); this.calcTotal(); this.saveCart(); },
  onCheckout() {
    const selected = this.data.items.filter(i => i.selected);
    if (selected.length === 0) {
      wx.showToast({ title: '请先勾选要结算的服务', icon: 'none' });
      return;
    }
    // 服务/预约订单需在服务详情页选择技师与服务时间，暂不提供独立结算页
    wx.showModal({
      title: '提示',
      content: '预约下单需选择技师与服务时间，请到服务详情页完成下单',
      confirmText: '去选服务',
      success: (r) => { if (r.confirm) wx.switchTab({ url: '/pages/service/list' }); }
    });
  }
});
