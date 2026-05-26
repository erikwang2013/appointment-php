const baseUrl = 'http://localhost:8788/api';

function request(method, url, data, auth = false) {
  return new Promise((resolve, reject) => {
    const header = { 'API-Version': 'v1' };
    if (auth) {
      const token = wx.getStorageSync('token');
      if (token) header['Authorization'] = 'Bearer ' + token;
    }
    wx.request({
      url: baseUrl + url,
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
