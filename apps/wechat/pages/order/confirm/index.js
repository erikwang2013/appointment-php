const api = require('../../../utils/api');
const subscribe = require('../../../utils/subscribe');

Page({
  data: {
    service: { id: '', name: '', price: 0, image: '' },
    technicianName: '',
    serviceTime: '',
    remark: '',
    coupons: [],
    selectedCoupon: null,
    discount: 0,
    total: 0,
    loadingCoupons: true,
    paying: false
  },
  onLoad(options) {
    const price = parseFloat(options.price) || 0;
    this.setData({
      service: {
        id: options.id || '',
        name: options.name || '',
        price: price,
        image: options.image || '',
        technician_id: options.technician_id || ''
      },
      technicianName: options.technician_name || '',
      serviceTime: options.service_time || '',
      remark: options.remark || '',
      total: price
    });
    this.loadCoupons();
  },
  async loadCoupons() {
    try {
      const res = await api.authGet('/marketing/coupons', { status: 'available' });
      const total = this.data.total;
      const list = (res.data || []).map(c => {
        const coupon = c.coupon || {};
        const min = parseFloat(coupon.min_amount) || 0;
        return Object.assign({}, c, { usable: total >= min });
      });
      this.setData({ coupons: list });
    } catch (e) { /* 拉券失败不影响下单 */ }
    finally {
      this.setData({ loadingCoupons: false });
    }
  },
  couponText(c) {
    const coupon = c.coupon || {};
    if (coupon.type === 'percent') {
      return (parseFloat(coupon.amount) || 0) + '%优惠';
    }
    return '¥' + (parseFloat(coupon.amount) || 0);
  },
  onCouponTap(e) {
    const idx = Number(e.currentTarget.dataset.index);
    const coupon = this.data.coupons[idx];
    if (!coupon) return;
    if (!coupon.usable) {
      wx.showToast({ title: '未满足使用门槛', icon: 'none' });
      return;
    }
    const selected = this.data.selectedCoupon && this.data.selectedCoupon.id === coupon.id ? null : coupon;
    this.setData({ selectedCoupon: selected }, () => this.calcDiscount());
  },
  calcDiscount() {
    const total = this.data.total;
    const c = this.data.selectedCoupon;
    let discount = 0;
    if (c) {
      const coupon = c.coupon || {};
      const amount = parseFloat(coupon.amount) || 0;
      discount = coupon.type === 'percent' ? total * amount / 100 : amount;
      discount = Math.min(discount, total);
      discount = Math.round(discount * 100) / 100;
    }
    this.setData({ discount });
  },
  async onCreate(e) {
    const channel = e.currentTarget.dataset.channel || 'wechat';
    if (this.data.paying) return;
    const { service, technicianName, serviceTime, remark, selectedCoupon } = this.data;
    if (!service.id || !serviceTime) {
      wx.showToast({ title: '订单信息不完整', icon: 'none' });
      return;
    }
    this.setData({ paying: true });
    try {
      const orderData = {
        items: [{
          target_type: 'service',
          target_id: service.id,
          name: service.name,
          cover_image: service.image,
          price: service.price,
          quantity: 1
        }],
        order_type: 'appointment',
        technician_id: service.technician_id || '',
        service_time: serviceTime,
        remark: remark || ''
      };
      if (selectedCoupon) orderData.user_coupon_id = selectedCoupon.id;
      const res = await api.authPost('/order', orderData);
      const order = res.data;
      wx.showToast({ title: '订单创建成功', icon: 'success' });
      // 预约成功即请求订阅授权（用户手势回调内），拒绝/失败静默处理
      subscribe.requestSubscribe(['order_confirm', 'service_reminder', 'technician_assigned']);
      // 拉起支付（微信 sign_params / 余额 balance）；零元单服务端直接返回支付成功
      try {
        const payData = channel === 'balance' ? { pay_channel: 'balance' } : {};
        const payRes = await api.authPost('/order/pay/' + order.id, payData);
        if (channel !== 'balance') {
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
        }
        wx.showToast({ title: '支付成功', icon: 'success' });
        setTimeout(() => wx.redirectTo({ url: '/pages/order/detail?id=' + order.id }), 1200);
      } catch (payErr) {
        const msg = String((payErr && payErr.message) || '');
        wx.showToast({
          title: msg.indexOf('cancel') >= 0 ? '已取消支付' : '支付失败，请稍后重试',
          icon: 'none'
        });
        setTimeout(() => wx.redirectTo({ url: '/pages/order/detail?id=' + order.id }), 1500);
      }
    } catch (e) {
      wx.showToast({ title: (e && e.message) || '下单失败', icon: 'none' });
    } finally {
      this.setData({ paying: false });
    }
  }
});
