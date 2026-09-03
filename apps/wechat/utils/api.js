const DEFAULT_BASE_URL = 'http://localhost:8788/api/v1';

// baseUrl 支持环境配置：优先取 app.js globalData.baseUrl（可在构建/发布时按环境修改），
// 兜底使用本地开发默认地址。延迟求值以兼容 getApp() 注册时序。
function getBaseUrl() {
  try {
    const app = getApp();
    if (app && app.globalData && app.globalData.baseUrl) {
      return app.globalData.baseUrl;
    }
  } catch (e) {}
  return DEFAULT_BASE_URL;
}

function request(method, url, data, auth = false) {
  return new Promise((resolve, reject) => {
    const header = {};
    if (auth) {
      const token = wx.getStorageSync('token');
      if (token) header['Authorization'] = 'Bearer ' + token;
    }
    wx.request({
      url: getBaseUrl() + url,
      method,
      data,
      header,
      success(res) {
        if (res.data.code === 0) resolve(res.data);
        else reject(res.data);
      },
      fail: reject
    });
  });
}

module.exports = {
  get: (url, data) => request('GET', url, data),
  post: (url, data) => request('POST', url, data, true),
  put: (url, data) => request('PUT', url, data, true),
  del: (url) => request('DELETE', url, {}, true),
  authPost: (url, data) => request('POST', url, data, true),
  authGet: (url, data) => request('GET', url, data, true),
};
