const api = require('../../utils/api');

const TYPE_TEXT = { recharge: '充值', consume: '消费', refund: '退款' };

Page({
  data: {
    wallet: { balance: '0.00', total_recharge: '0.00', total_consume: '0.00' },
    txns: [],
    hasMore: false,
    page: 1,
    amount: '',
    showRecharge: false,
    paying: false
  },

  onShow() { this.refresh(); },

  async refresh() {
    await Promise.all([this.loadWallet(), this.loadTxns(true)]);
  },

  async loadWallet() {
    try {
      const res = await api.authGet('/wallet/');
      const w = res.data;
      this.setData({
        wallet: {
          balance: Number(w.balance).toFixed(2),
          total_recharge: Number(w.total_recharge).toFixed(2),
          total_consume: Number(w.total_consume).toFixed(2)
        }
      });
    } catch (e) { wx.showToast({ title: '余额加载失败', icon: 'none' }); }
  },

  async loadTxns(reset) {
    const page = reset ? 1 : this.data.page + 1;
    try {
      const res = await api.authGet('/wallet/txns', { per_page: 15, page });
      const items = (res.data || []).map(t => ({
        id: t.id,
        type_text: t.type_text || TYPE_TEXT[t.type] || t.type,
        amount: Number(t.amount).toFixed(2),
        balance_after: Number(t.balance_after).toFixed(2),
        remark: t.remark || '',
        created_at: (t.created_at || '').slice(0, 16).replace('T', ' ')
      }));
      this.setData({
        txns: reset ? items : this.data.txns.concat(items),
        page,
        hasMore: !!(res.meta && res.meta.has_more)
      });
    } catch (e) { /* 静默 */ }
  },

  onReachBottom() {
    if (this.data.hasMore) this.loadTxns(false);
  },

  toggleRecharge() { this.setData({ showRecharge: !this.data.showRecharge, amount: '' }); },

  onAmountInput(e) { this.setData({ amount: e.detail.value }); },

  async onRecharge() {
    const amount = parseFloat(this.data.amount);
    if (!(amount > 0)) {
      wx.showToast({ title: '请输入有效金额', icon: 'none' });
      return;
    }
    if (amount > 50000) {
      wx.showToast({ title: '单笔充值不超过50000元', icon: 'none' });
      return;
    }
    this.setData({ paying: true });
    try {
      // 1. 创建充值单
      const created = await api.authPost('/wallet/recharge', { amount });
      const rechargeId = created.data.recharge_id;
      // 2. 发起微信支付
      const payRes = await api.authPost('/wallet/recharge/' + rechargeId + '/pay');
      const sign = payRes.data.sign_params;
      // 3. 调起微信收银台
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
      wx.showToast({ title: '充值成功', icon: 'success' });
      this.setData({ showRecharge: false, amount: '' });
      this.refresh();
    } catch (e) {
      if (e && e.message && e.message.indexOf('cancel') >= 0) {
        wx.showToast({ title: '已取消支付', icon: 'none' });
      } else {
        wx.showToast({ title: '充值失败，请重试', icon: 'none' });
      }
    } finally {
      this.setData({ paying: false });
    }
  }
});
