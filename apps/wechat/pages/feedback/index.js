const api = require('../../utils/api');

// 契约对齐：FeedbackController 仅接收 content（必填 ≤1000 字符）与 images（可选数组，需为 URL）。
// 无类型、无联系方式字段，页面按实际契约只提交 content。
Page({
  data: { content: '', maxLen: 1000, submitting: false },

  onInput(e) {
    const content = e.detail.value;
    if (content.length > this.data.maxLen) return;
    this.setData({ content });
  },

  onSubmit() {
    const content = this.data.content.trim();
    if (!content) {
      wx.showToast({ title: '请输入反馈内容', icon: 'none' });
      return;
    }
    if (this.data.submitting) return;
    this.setData({ submitting: true });
    api.authPost('/user/feedback', { content }).then(() => {
      wx.showToast({ title: '感谢您的反馈', icon: 'success' });
      setTimeout(() => wx.navigateBack(), 1200);
    }).catch((e) => {
      this.setData({ submitting: false });
      wx.showToast({ title: (e && e.message) || '提交失败', icon: 'none' });
    });
  }
});
