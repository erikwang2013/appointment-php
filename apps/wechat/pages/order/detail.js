const api = require('../../utils/api');
const subscribe = require('../../utils/subscribe');
const REFUND_STATUS_TEXT = { pending: '退款处理中', success: '已退款', failed: '退款失败' };
Page({
  data: { order: null, paying: false },
  onLoad(options) { this.loadDetail(options.id); },
  onPullDownRefresh() { this.loadDetail(this.data.order ? this.data.order.id : ''); },
  async loadDetail(id) {
    try {
      const res = await api.authGet('/order/detail/' + id);
      const order = res.data;
      if (order) order.refund_status_text = REFUND_STATUS_TEXT[order.refund_status] || '';
      this.setData({ order });
      wx.stopPullDownRefresh && wx.stopPullDownRefresh();
    }
    catch(e) { wx.showToast({title: '加载失败', icon: 'none'}); }
  },
  /** 微信支付 */
  async onPay() {
    const order = this.data.order;
    if (!order || this.data.paying) return;
    this.setData({ paying: true });
    try {
      const payRes = await api.authPost('/order/pay/' + order.id);
      const sign = payRes.data.sign_params;
      await new Promise((resolve, reject) => {
        wx.requestPayment({
          timeStamp: sign.timeStamp,
          nonceStr: sign.nonceStr,
          package: sign.package,
          signType: sign.signType,
          paySign: sign.paySign,
          success: resolve,
          fail: reject
        });
      });
      wx.showToast({ title: '支付成功', icon: 'success' });
      subscribe.requestSubscribe(['order_confirm', 'service_reminder', 'technician_assigned']);
      this.loadDetail(order.id);
    } catch (e) {
      if (e && e.message && String(e.message).indexOf('cancel') >= 0) {
        wx.showToast({ title: '已取消支付', icon: 'none' });
      } else {
        wx.showToast({ title: '支付失败，请重试', icon: 'none' });
      }
    } finally {
      this.setData({ paying: false });
    }
  },
  /** 余额支付（pay_channel=balance，服务端事务内扣款+标记支付） */
  async onBalancePay() {
    const order = this.data.order;
    if (!order || this.data.paying) return;
    this.setData({ paying: true });
    try {
      await api.authPost('/order/pay/' + order.id, { pay_channel: 'balance' });
      wx.showToast({ title: '余额支付成功', icon: 'success' });
      subscribe.requestSubscribe(['order_confirm', 'service_reminder', 'technician_assigned']);
      this.loadDetail(order.id);
    } catch (e) {
      wx.showToast({ title: (e && e.message) || '余额支付失败', icon: 'none' });
    } finally {
      this.setData({ paying: false });
    }
  },
  onCancel() { /* POST /api/order/cancel/{id} */ },
  onRefund() { /* POST /api/order/refund/{id} */ }
});
