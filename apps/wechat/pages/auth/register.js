const api = require('../../utils/api');

Page({
  data: {
    phone: '',
    code: '',
    password: '',
    confirmPassword: '',
    referralCode: '',
    agreePrivacy: false
  },
  inputPhone(e) { this.setData({ phone: e.detail.value }); },
  inputCode(e) { this.setData({ code: e.detail.value }); },
  inputPassword(e) { this.setData({ password: e.detail.value }); },
  inputConfirmPassword(e) { this.setData({ confirmPassword: e.detail.value }); },
  inputReferral(e) { this.setData({ referralCode: e.detail.value }); },
  toggleAgree() { this.setData({ agreePrivacy: !this.data.agreePrivacy }); },
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
  doRegister() {
    const { phone, code, password, confirmPassword, referralCode, agreePrivacy } = this.data;
    if (!phone || !code || !password) {
      wx.showToast({ title: '请填写完整信息', icon: 'none' });
      return;
    }
    if (password !== confirmPassword) {
      wx.showToast({ title: '两次密码不一致', icon: 'none' });
      return;
    }
    if (!agreePrivacy) {
      wx.showToast({ title: '请阅读并同意隐私协议', icon: 'none' });
      return;
    }
    api.post('/auth/register', {
      phone, code, password, referralCode
    }).then(res => {
      wx.showToast({ title: '注册成功', icon: 'success' });
      setTimeout(() => wx.navigateBack(), 1500);
    }).catch(err => {
      wx.showToast({ title: err.msg || '注册失败', icon: 'none' });
    });
  },
  goLogin() {
    wx.navigateBack();
  }
});
