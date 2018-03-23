//app.js

App({
    onLaunch: function () {

        // 登录
        wx.login({
            success: res => {
                // 发送 res.code 到后台换取 openId, sessionKey, unionId
                if (res.code) {
                    wx.request({
                        url: "https://hygs.web.mai022.com/wxapp/sessionKey.php?code=" + res.code,
                        dataType: "json",
                        success: function (o) {
                            //console.log(o)
                            wx.setStorageSync('sessionKey', o.data.sessionKey);
                            wx.setStorageSync('openid', o.data.openid);
                        }
                    })
                }
            },
        })
        // 获取用户信息
        wx.getSetting({
            success: res => {
                //console.log(res)
                if (res.authSetting['scope.userInfo']) {
                    // 已经授权，可以直接调用 getUserInfo 获取头像昵称，不会弹框
                    wx.getUserInfo({
                        success: res => {
                            // 可以将 res 发送给后台解码出 unionId
                            this.globalData.userInfo = res.userInfo
                            // 由于 getUserInfo 是网络请求，可能会在 Page.onLoad 之后才返回
                            // 所以此处加入 callback 以防止这种情况
                            if (this.userInfoReadyCallback) {
                                this.userInfoReadyCallback(res)
                            }

                            this.getUserBaseInfo(res);

                        }
                    })
                }
            }
        })
        
    },
    //获取用户信息
    getUserBaseInfo: function (data) {
        var that = this;
        wx.request({
            url: "https://hygs.web.mai022.com/wxapp/wxdecode.php",
            data: {
                encryptedData: data.encryptedData,
                iv: data.iv,
                sessionKey: wx.getStorageSync('sessionKey'),
            },
            dataType: "json",
            success: function (o) {
                
                if (wx.getStorageSync('platform') == 'devtools' || wx.getStorageSync('platform') == 'ios') {
                    var oo = o.data;//工具用
                } else {
                    var oo = JSON.parse(o.data.trim());//线上用
                }
                //console.log(oo)
                if (oo == '') {
                    that.getUserBaseInfo(data)
                } else {
                    that.insertUser(oo);
                }

            }
        })
    },
    //用户信息入库
    insertUser: function (user) {
        var that = this;
        
        wx.request({
            url: 'https://hygs.web.mai022.com/wxapp/index.php',
            data: { a: 'insertUser', openid: user.openId, avatar: user.avatarUrl, nickname: user.nickName },
            success: function (res) {
                if (res.data.code=='no'){
                    that.insertUser(user);
                }else{
                    //console.log(res)
                }
            }
        })
    },
    globalData: {
        userInfo: null,
    }
})