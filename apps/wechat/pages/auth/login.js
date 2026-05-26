const api = require('../../utils/api');

Page({
  data: {
    mode: 'password',
    phone: '',
    password: '',
    code: '',
    agreePrivacy: false
  },
  switchMode(e) {
    this.setData({ mode: e.currentTarget.dataset.mode });
  },
  inputPhone(e) {
    this.setData({ phone: e.detail.value });
  },
  inputPassword(e) {
    this.setData({ password: e.detail.value });
  },
  inputCode(e) {
    this.setData({ code: e.detail.value });
  },
  toggleAgree() {
    this.setData({ agreePrivacy: !this.data.agreePrivacy });
  },
  sendCode() {
    if (!this.data.phone) {
      wx.showToast({ title: '请输入手机号', icon: 'none' });
      return;
    }
    api.post('/auth/send-sms', { phone: this.data.phone }).then(() => {
      wx.showToast({ title: '验证码已发送', icon: 'success' });
    }).catch(err => {
      wx.showToast({ title: err.msg || '发送失败', icon: 'none' });
    });
  },
  doLogin() {
    const { mode, phone, password, code, agreePrivacy } = this.data;
    if (!phone) {
      wx.showToast({ title: '请输入手机号', icon: 'none' });
      return;
    }
    if (!agreePrivacy) {
      wx.showToast({ title: '请阅读并同意隐私协议', icon: 'none' });
      return;
    }
    const data = mode === 'password' ? { phone, password } : { phone, code };
    api.get('/auth/login', data).then(res => {
      const token = res.data.token;
      wx.setStorageSync('token', token);
      getApp().globalData.token = token;
      wx.showToast({ title: '登录成功', icon: 'success' });
      setTimeout(() => wx.switchTab({ url: '/pages/home/index' }), 1000);
    }).catch(err => {
      wx.showToast({ title: err.msg || '登录失败', icon: 'none' });
    });
  },
  wechatLogin() {
    wx.getUserProfile({
      desc: '用于完善会员信息',
      success: (res) => {
        const userInfo = res.userInfo;
        getApp().globalData.userInfo = userInfo;
        api.post('/auth/wechat-login', { userInfo }).then(res => {
          wx.setStorageSync('token', res.data.token);
          getApp().globalData.token = res.data.token;
          wx.showToast({ title: '登录成功', icon: 'success' });
          setTimeout(() => wx.switchTab({ url: '/pages/home/index' }), 1000);
        }).catch(err => {
          wx.showToast({ title: err.msg || '微信登录失败', icon: 'none' });
        });
      }
    });
  },
  goRegister() {
    wx.navigateTo({ url: '/pages/auth/register' });
  },
  goForgetPassword() {
    wx.navigateTo({ url: '/pages/auth/forget-password' });
  },
  guestEntry() {
    wx.switchTab({ url: '/pages/home/index' });
  }
});
