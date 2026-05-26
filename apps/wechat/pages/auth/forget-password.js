const api = require('../../utils/api');

Page({
  data: {
    phone: '',
    code: '',
    password: '',
    confirmPassword: ''
  },
  inputPhone(e) { this.setData({ phone: e.detail.value }); },
  inputCode(e) { this.setData({ code: e.detail.value }); },
  inputPassword(e) { this.setData({ password: e.detail.value }); },
  inputConfirmPassword(e) { this.setData({ confirmPassword: e.detail.value }); },
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
  doReset() {
    const { phone, code, password, confirmPassword } = this.data;
    if (!phone || !code || !password) {
      wx.showToast({ title: '请填写完整信息', icon: 'none' });
      return;
    }
    if (password !== confirmPassword) {
      wx.showToast({ title: '两次密码不一致', icon: 'none' });
      return;
    }
    api.post('/auth/reset-password', { phone, code, password }).then(() => {
      wx.showToast({ title: '密码重置成功', icon: 'success' });
      setTimeout(() => wx.navigateBack(), 1500);
    }).catch(err => {
      wx.showToast({ title: err.msg || '重置失败', icon: 'none' });
    });
  }
});
