//index.js
//获取应用实例
const app = getApp()
const config = require('../../config.js')

Page({
    data: {
        wxrun: "0",
        userInfo: {},
        mymodal: false,
        ads: null,
        big_img: '',
        yibiaopan: false,
        isIos:false,
    },

    onLoad: function (option) {
        var that = this;

        // if (app.globalData.userInfo) {
        //     //  console.log(app.globalData.userInfo)
        //     this.setData({
        //         userInfo: app.globalData.userInfo,
        //         hasUserInfo: true
        //     })
        // } else if (this.data.canIUse) {
        //     // 由于 getUserInfo 是网络请求，可能会在 Page.onLoad 之后才返回
        //     // 所以此处加入 callback 以防止这种情况
        //     app.userInfoReadyCallback = res => {
        //         this.setData({
        //             userInfo: res.userInfo,
        //             hasUserInfo: true
        //         })
        //     }
        // } else {
        //     // 在没有 open-type=getUserInfo 版本的兼容处理
        //     wx.getUserInfo({
        //         success: res => {
        //             app.globalData.userInfo = res.userInfo
        //             this.setData({
        //                 userInfo: res.userInfo,
        //                 hasUserInfo: true
        //             })
        //         }
        //     })
        // }

        wx.setNavigationBarTitle({ title: '微信运动' })
        wx.getSystemInfo({
            success: function (res) {
                //console.log(res)
                wx.setStorageSync('platform', res.platform);
                wx.setStorageSync('screenWidth', res.screenWidth);
            }
        })

        //获取微信运动步数
        that.getWxRunStep();
        //获取广告
        that.getAds();
        this.getUid();
        
        if (option.uid != undefined && option.logtype != undefined && option.rid != undefined && option.keyword != undefined) {
            //索取或赠送
            that.linkGiftLog(option);//关联索取和赠送
        }
        if (option.tmid != undefined) {
            //加入团队
            that.joinTeam(option.tmid);//加入团队
        }

    },
    onReady: function (e) {
       
        this.canvasArc();
        this.getFuZi();

    },
    //关联索取和赠送
    linkGiftLog(option) {
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'linkGiftLog', uid: wx.getStorageSync('uid'), fromid: option.uid, logtype: option.logtype, rid: option.rid, keyword: option.keyword },
            success: function (res) {
                if (res.data.code == 'ok' && res.data.logtype == '1') {
                    wx.showToast({
                        title: option.keyword + '字存入账号',
                        icon: 'success',
                        image: '',
                        duration: 2000
                    })
                }
            }
        })
    },
    //获取uid
    getUid: function () {
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getUid', openid: wx.getStorageSync('openid') },
            success: function (res) {
                wx.setStorageSync('uid', res.data.id);
                wx.setStorageSync('wxuid', res.data.wxuid);
                if (res.data.wxuid == 0) {
                    that.redirectTowap();
                }
            }
            
        })
    },
    //获取微信运动步数
    getWxRunStep: function () {
        //微信步数 
        var that = this;
        wx.getWeRunData({
            success(res) {

                wx.request({
                    //   url: "https://ghqp.mai022.com/wx/wxdecode.php",
                    url: config.service.decodeUrl,
                    data: {
                        encryptedData: res.encryptedData,
                        iv: res.iv,
                        sessionKey: wx.getStorageSync('sessionKey'),
                    },
                    dataType: "json",
                    success: function (o) {
                        
                        if (wx.getStorageSync('platform') == 'devtools' || wx.getStorageSync('platform') == 'ios') {
                            var oo = o.data;//工具用
                            that.setData({
                                isIos: true,
                            })
                        } else {
                            var oo = JSON.parse(o.data.trim());//线上用
                        }

                        //获取当天的步数
                        wx.setStorageSync('wx_step', oo.stepInfoList[30].step);

                        that.setData({
                            wxrun: oo.stepInfoList[30].step
                        });
                        that.syncStep(oo.stepInfoList);
                        
                    }
                })
            },
        })
    },
    //获取福字
    getFuZi: function () {
        var that = this;
        
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getRandChar', rid: wx.getStorageSync('zi_rid'), openid: wx.getStorageSync('openid'), step: wx.getStorageSync('wx_step') },
            success: function (res) {
                //console.log(res)
                if (res.data.code == 'ok') {
                    var imgs = res.data.data;
                    if (imgs.length != 0) {
                        that.setData({
                            mymodal: true,
                            big_img: imgs[0]['img'],
                            yibiaopan: true,
                            
                        })
                        imgs.shift();
                        wx.setStorageSync('fuzi', imgs)
                    }
                }
            }
        })
    },
    //获取广告
    getAds: function () {
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getAds' },
            success: function (res) {
                //console.log(res)
                wx.setStorageSync('zi_rid', res.data[0].rid);
                that.setData({
                    ads: res.data,
                })
            }
        })
    },
    //刻画仪表盘
    canvasArc: function () {
        // 使用 wx.createContext 获取绘图上下文 context
        var context = wx.createCanvasContext('firstCanvas');
        var step = wx.getStorageSync('wx_step') + " 步";
        var end = (wx.getStorageSync('wx_step') / 20000 * 1.4 * Math.PI) + 0.8 * Math.PI;


        context.beginPath()//开始画白色的底
        context.arc(150, 120, 100, 0.8 * Math.PI, 2.2 * Math.PI, false)
        context.setStrokeStyle("#ffffff")
        context.setLineWidth(10)
        context.stroke()

        context.beginPath()//开始画进度
        context.arc(150, 120, 100, 0.8 * Math.PI, end, false)
        context.setStrokeStyle("#12B0DF")
        context.setLineWidth(10)
        context.stroke()

        context.draw()
    },
    //动画
    animationArc: function () {

    },
    //捐步
    giveStep: function () {
        var that = this;

        this.setData({
            mymodal: true,
        })
    },
    //获取步数
    getStep: function () {

        this.setData({
            wxrun: wx.getStorageSync('wx_step')
        })
    },
    //跳转
    redirectTo: function (e) {
        //console.log(e);
        wx.navigateTo({
            url: e.target.dataset.link,
        })
    },
    //转发
    onShareAppMessage: function (res) {
        if (res.from === 'button') {
            // 来自页面内转发按钮,关闭分享
            this.cancel();
        }
        return {
            title: '微信运动,集字领福利',
            path: '/pages/index/index',
            imageUrl: config.service.imageUrl+"indexshare.jpg",
            success: function (res) {
                // 转发成功
                //console.log('转发成功');
                wx.showToast({
                    title: '转发成功',
                    icon: 'success',
                    duration: 2000
                })
            },
            fail: function (res) {
                // 转发失败
                console.log('转发失败');
            }
        }
    },
    //关闭modal框
    cancel: function () {
        var imgs = wx.getStorageSync('fuzi');
        //console.log(imgs);
        if (imgs.length == 0) {
            this.setData({
                mymodal: false,
                yibiaopan: false,
            })

        } else {
            this.setData({
                big_img: imgs[0]['img'],
            })
            imgs.shift();
            wx.setStorageSync('fuzi', imgs);
        }

    },
    //关闭ios提示
    close:function(){
        this.setData({
            isIos:false,
        })
    },
    //置顶小程序
    setTopBarText: function () {
        wx.setTopBarText({
            text: '微信运动',
            success: function () {
                console.log('置顶成功')
                wx.showToast({
                    title: '置顶成功',
                    icon: 'success',
                    duration: 2000
                })
            },
            fail: function () {
                console.log('置顶失败')
            }
        })
    },
    //跳转获取微信openid
    redirectTowap:function(){
        wx.redirectTo({
            url: '../wap/wap',
        })
    },
    //同步步数
    syncStep(step){
        
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'syncStep', openid: wx.getStorageSync('openid'), step: JSON.stringify(step) },
            success: function (res) {
                //console.log(res)
            }
        })
    },
    //跳转到排行榜页面
    personRank: function () {
        wx.navigateTo({
            url: '../rank/rank?cid=1',
        })
    },
    //跳转到排行榜页面
    teamRank: function () {
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'isrank',openid: wx.getStorageSync('openid')},
            success: function (res) {
                if (res.data.code == 'ok') {
                    wx.navigateTo({
                        url: '../rank/rank?cid=2&istm='+res.data.data,
                    })
                } else {
                    wx.showToast({
                        title: res.data.msg,
                        icon: 'none',
                        duration: 2000
                    })
                }
            }
        })
    },
    //加入团队
    joinTeam:function(tmid){
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'joinTeam', openid: wx.getStorageSync('openid'), tmid: tmid},
            success: function (res) {
                wx.showToast({
                    title: res.data.msg,
                    icon: 'none',
                    duration: 2000
                })
            }
        })
    }  
})
