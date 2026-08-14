const api = require('../../utils/api');
const drawQrcode = require('../../utils/qrcode');

// 契约对齐：ReferralController
// GET /user/referral          → { referral_code, referral_count, first_order_count, earned_points }
// GET /user/referral/qrcode   → { referral_code, invite_url }（invite_url 为链接字符串，非图片）
// GET /user/referral/referred-users → [{ id, user_id, nickname, avatar, registered_at,
//        first_order_at, has_first_order, reward_type, reward_amount, rewarded_at }]
function formatDate(t) {
  if (!t) return '';
  const d = new Date(String(t).replace(/-/g, '/'));
  if (isNaN(d.getTime())) return t;
  return d.getFullYear() + '-' + (d.getMonth() + 1 < 10 ? '0' : '') + (d.getMonth() + 1) + '-' +
    (d.getDate() < 10 ? '0' : '') + d.getDate();
}

function decorateReferred(item) {
  let rewardText = '';
  if (item.reward_amount) rewardText = '奖励 ' + (item.reward_type === 'points' ? item.reward_amount + ' 积分' : '¥' + item.reward_amount);
  return {
    ...item,
    registered_text: formatDate(item.registered_at),
    first_order_text: item.has_first_order ? formatDate(item.first_order_at) : '',
    reward_text: rewardText
  };
}

Page({
  data: { info: null, inviteUrl: '', referred: [], loading: false, loaded: false },

  onShow() {
    this.load();
  },

  onPullDownRefresh() {
    this.load().finally(() => wx.stopPullDownRefresh());
  },

  async load() {
    if (this._loading) return;
    this._loading = true;
    this.setData({ loading: true });
    try {
      const [infoRes, qrcodeRes, referredRes] = await Promise.all([
        api.authGet('/user/referral'),
        api.authGet('/user/referral/qrcode'),
        api.authGet('/user/referral/referred-users')
      ]);
      this.setData({
        info: infoRes.data,
        inviteUrl: qrcodeRes.data && qrcodeRes.data.invite_url || '',
        referred: (referredRes.data || []).map(decorateReferred),
        loaded: true
      }, () => this.drawQrcode());
    } catch (e) {
      wx.showToast({ title: '加载失败', icon: 'none' });
    } finally {
      this._loading = false;
      this.setData({ loading: false });
    }
  },

  onCopyCode() {
    const code = this.data.info && this.data.info.referral_code;
    if (!code) return;
    wx.setClipboardData({ data: code });
  },

  drawQrcode() {
    const url = this.data.inviteUrl;
    if (!url) return;
    drawQrcode({
      canvasId: 'inviteQr',
      _this: this,
      text: url,
      width: 200,
      height: 200,
      foreground: '#333333',
      background: '#ffffff'
    });
  },

  onCopyUrl() {
    if (!this.data.inviteUrl) return;
    wx.setClipboardData({ data: this.data.inviteUrl });
  }
});
