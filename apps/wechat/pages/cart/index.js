const api = require('../../utils/api');
Page({
  data: { items: [], totalAmount: '0.00', allSelected: true },
  onShow() { this.loadCart(); },
  async loadCart() {
    try { const res = await api.authGet('/order/cart'); this.setData({ items: res.data || [] }); this.calcTotal(); }
    catch(e) { wx.showToast({title: '加载失败', icon: 'none'}); }
  },
  calcTotal() { let total = 0; this.data.items.filter(i => i.selected).forEach(i => total += i.price * i.quantity); this.setData({ totalAmount: total.toFixed(2) }); },
  onQuantityChange(e) { const { index, type } = e.currentTarget.dataset; const items = this.data.items; if (type === 'plus') items[index].quantity++; else if (items[index].quantity > 1) items[index].quantity--; this.setData({ items }); this.calcTotal(); },
  onSelect(e) { const { index } = e.currentTarget.dataset; this.data.items[index].selected = !this.data.items[index].selected; this.setData({ items: this.data.items }); this.calcTotal(); },
  onDelete(e) { const { index } = e.currentTarget.dataset; this.data.items.splice(index, 1); this.setData({ items: this.data.items }); this.calcTotal(); },
  onCheckout() { wx.navigateTo({ url: '/pages/order/confirm' }); }
});
