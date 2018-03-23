//badge.js
const util = require('../../utils/util.js')
const config = require('../../config.js')

Page({
    data: {
        bg_img: '',
        all: null,
        count: 0,
        keywords: '',
        animationData: {},
        step1: false,
        step2: true,
        step3: false,
        step4: true,
        step5: true,
        show: '0',
        actTime: '',
        actContent: '',
        giftContent: '',
        allLog: null,
        allres: null,
        current: 0,
        iscollect: false,
        mymodal: false,
        gifttype: false,
        gift_bgimg: '',
        lastX: 0,
        phone: '',
        shopcate:0,
        time_img: config.service.imageUrl +"time.jpg",
        gift_img: config.service.imageUrl +"gift.jpg",
        about_img: config.service.imageUrl +"about.jpg",
        givebtnl:'#fff',
        givebtnr: '#fff',
        getbtnl: '#fff',
        getbtnr: '#fff',
        smallbdc: '#fff',
        inputc: '#fff',
        isIphoneX:false,
    },
    onLoad: function (option) {

        wx.showLoading({
            title: '加载中',
            mask:true,
        })
        wx.setStorageSync('rid', option.rid);
        this.getBgimg();//背景图
        this.getMyColor();//获取自定义颜色
        this.isrenzheng();//是否认证
        this.getIsCollect();
        
        this.getGiftRes();//已集齐兑换的总人数
        this.getActdetail();//活动说明
        this.getGiveLog();//互赠记录
        this.getGiftList();//获奖名单

        let that=this;
        wx.getSystemInfo({
            success: function (res) {
                let model = res.model.substring(0,8);
                //console.log(model);
                if (model == 'iPhone X') {
                    that.setData({
                        isIphoneX: true
                    })
                }
            }
        })
        setTimeout(function () {
            wx.hideLoading()
        }, 2000)
    },
    //单选发生改变时
    radioChange:function(e){
        var that = this;
        that.setData({
            shopcate: e.detail.value
        })
    },
    //校验手机号
    checkphone: function (e) {
        var that = this;
        that.setData({
            phone: e.detail.value
        })
        if (!/^(13[0-9]|14[5|7]|15[0|1|2|3|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9])\d{8}$/.test(that.data.phone)){
            wx.showToast({
                title: '手机号格式不正确',
                icon: 'none',
                image:'',
                duration: 2000
            }) 
        }
    },
    //确认兑换
    formSubmit: function (e) {
        //console.log(e)
        var that = this;
        var phone = that.data.phone;
        var shopcate = that.data.shopcate;
        if (shopcate == 0){
            wx.showToast({
                title: '请选择运营商',
                icon: 'none',
                image: '',
                duration: 2000
            });
            return false;
        }
        if (!/^(13[0-9]|14[5|7]|15[0|1|2|3|5|6|7|8|9]|18[0|1|2|3|5|6|7|8|9])\d{8}$/.test(that.data.phone)) {
            wx.showToast({
                title: '手机号格式不正确',
                icon: 'none',
                image: '',
                duration: 2000
            })
            return false;
        }else{
            var giftType = wx.getStorageSync('giftType');
            if (giftType == '2'){
                wx.request({
                    url: config.service.requestUrl,
                    data: { a: 'duihuanGift', rid: wx.getStorageSync('rid'), uid: wx.getStorageSync('uid'), phone: phone, shopcate: shopcate, gifttype: giftType},
                    success: function (res) {
                        var code = res.data.code;
                        if (res.data.code=='ok'){
                            var title='兑换成功';
                            wx.setStorageSync('isCollect', 'no');
                            
                        }else{
                            var title = '兑换失败';
                        }
                        wx.showToast({
                            title: title,
                            icon: 'success',
                            image: '',
                            duration: 3000,
                            success:function(){
                                if (code == 'ok') {
                                    wx.redirectTo({
                                        url: '/pages/index/index',
                                    })
                                }
                            }
                        })
                    }
                })
            }   
        }
    },

    //是否认证
    isrenzheng:function(){
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'isrenzheng', openid: wx.getStorageSync('openid') },
            success: function (res) {
                if(res.data.code=='ok'){
                    that.setData({
                        mymodal:false,
                    })
                }else{
                    that.setData({
                        mymodal: true,
                    })
                }
            }
        })
    },

    //获取是否集齐福字
    getIsCollect: function () {
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getIsCollect', rid: wx.getStorageSync('rid'), openid: wx.getStorageSync('openid') },
            success: function (res) {
                
                wx.setStorageSync('isCollect', res.data.code);
                if (res.data.code == 'ok') {
                    that.setData({
                        iscollect: true,
                        gift_bgimg: res.data.gift_bgimg
                    })
                    wx.setStorageSync('giftType', res.data.gifttype);
                }else{
                    that.setData({
                        iscollect: false,
                    })
                    that.getUserFuzi();//当前用户收集的字详情
                }
            }
        })
    },
    //获取bgimg
    getBgimg: function () {
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getBgimg', rid: wx.getStorageSync('rid') },
            success: function (res) {
                //console.log(res)
                that.setData({
                    bg_img: res.data.img,
                })
            }
        })
    },
    //获取自定义颜色
    getMyColor:function(){
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getMyColor', rid: wx.getStorageSync('rid') },
            success: function (res) {
                //console.log(res);
                that.setData({
                    givebtnl: res.data.givebtncolorl,
                    givebtnr: res.data.givebtncolorr,
                    getbtnl: res.data.getbtncolorl,
                    getbtnr: res.data.getbtncolorr,
                    smallbdc: res.data.smallbdcolor,
                    inputc: res.data.inputcolor,
                })
            }
        })
    },
    //获取获奖名单
    getGiftList: function () {
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getGiftRes', rid: wx.getStorageSync('rid') },
            success: function (res) {
                
                if (res.data.length > 0) {
                    that.setData({
                        allres: res.data,
                    })
                }
            }
        })
    },
    //获取互赠记录
    getGiveLog: function () {
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getGiveLog', rid: wx.getStorageSync('rid'), openid: wx.getStorageSync('openid') },
            success: function (res) {
                
                if (res.data.length > 0){
                    that.setData({
                        allLog: res.data,
                    })
                }
            }
        })
    },
    //获取活动说明
    getActdetail: function () {
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getActDetail', rid: wx.getStorageSync('rid') },
            success: function (res) {
                //console.log(res)
                that.setData({
                    actTime: res.data.startTime + " - " + res.data.endTime,
                    actContent: res.data.content,
                    giftContent: res.data.giftContent,
                })
            }
        })
    },
    //获取兑换总数
    getGiftRes: function () {
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getAllNum', rid: wx.getStorageSync('rid') },
            success: function (res) {
                //console.log(res)
                that.setData({
                    count: res.data.total,
                    keywords: res.data.keywords,
                })
            }
        })
    },
    //获取用户的福字
    getUserFuzi: function () {
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getUserFuzi', rid: wx.getStorageSync('rid'), openid: wx.getStorageSync('openid') },
            success: function (res) {
                //console.log(res)
                that.setData({
                    all: res.data,
                })
            }
        })
    },
    //上次位置
    lastX: function (e) {
        let x = e.touches[0].pageX
        this.setData({
            lastX: x,
        })
    },
    //动画
    animationData: function (e) {

        var animation = wx.createAnimation({
            duration: 1000,
            timingFunction: 'ease',
        })

        this.animation = animation

        let X = e.touches[0].pageX
        if (X > this.data.lastX) {
            animation.scale(0.8, 0.8).translate(345).step();
        } else {
            animation.scale(1, 1).translate(-345).step();
        }

        this.setData({
            animationData: animation.export(),
            lastX: X
        })
    },
    //显示选中的字
    show: function (e) {
        //console.log(e)
        var id = e.currentTarget.dataset.id;
        this.setData({
            show: id,
            current: id,
        })

    },
    //切换福字
    qiehuan: function (e) {
        this.setData({
            show: e.detail.current,
        })
    },
    //上一步
    preStep: function () {
        this.setData({
            step1: false,
            step2: true,
        })
    },
    //下一步
    nextStep: function () {
        
        this.setData({
            step1: true,
            step2: false,
        })
    },
    step3: function (e) {

        this.setData({
            step3: false,
            step4: true,
            step5: true,
        })
    },
    step4: function (e) {
        //console.log(e)
        this.setData({
            step3: true,
            step4: false,
            step5: true,
        })
    },
    step5: function (e) {
        this.setData({
            step3: true,
            step4: true,
            step5: false,
        })
    },

    //转发
    onShareAppMessage: function (res) {
        var title = '微信运动';
        var that = this;
        var kwd = res.target.dataset.keyword;
        var id = res.target.id;
        var sharepath = '/pages/index/index';
        var imageurl = '';
        //console.log(res)
        if (res.from === 'button') {
            imageurl = res.target.dataset.img;
            // 来自页面内转发按钮,关闭分享
            if (id == 'givef') {
                title = "微信运动,送你一个“" + kwd + "”字";
                sharepath = '/pages/index/index?uid=' + wx.getStorageSync('uid') + '&logtype=1&rid=' + wx.getStorageSync('rid') + '&keyword=' + kwd;
            } else if (id == 'getf') {
                title = "微信运动,我还差一个“" + kwd + "”字";
                sharepath = '/pages/index/index?uid=' + wx.getStorageSync('uid') + '&logtype=2&rid=' + wx.getStorageSync('rid') + '&keyword=' + kwd;
            }

        }
        return {
            title: title,
            path: sharepath,
            imageUrl: imageurl,
            success: function (res) {
                // 转发成功
                if (id == 'givef') {
                    that.giveFrind(kwd);
                } else if (id == 'getf') {
                    that.getFrind(kwd)
                }
                
            },
            fail: function (res) {
                // 转发失败
                console.log('转发失败');
            }
        }
    },
    //赠送好友字
    giveFrind: function (kwd) {
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'giveFrind', rid: wx.getStorageSync('rid'), openid: wx.getStorageSync('openid'), keyword: kwd },
            success: function (res) {
                //console.log(res.data.data)
            }
        })
    },
    //向好友索取字
    getFrind: function (kwd) {
        var that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getFrind', rid: wx.getStorageSync('rid'), openid: wx.getStorageSync('openid'), keyword: kwd },
            success: function (res) {
                //console.log(res.data.data)
            }
        })
    },

    //关闭modal框
    cancel: function () {
        this.setData({
            hidden: true,
        })
    },
    //关闭自定义modal框
    close: function () {
        this.setData({
            mymodal: false,
        })
    },
})
