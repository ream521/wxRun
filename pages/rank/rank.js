// pages/rank/rank.js
const config = require('../../config.js')
const wxCharts = require('../../utils/wxcharts.js');
var lineChart = null;
Page({

    /**
     * 页面的初始数据
     */
    data: {
        rankList:null,
        rank:null,
        isTeam:false,
        today:true,
        all:false,
        istm:false,
        show:false,
        preimg:'',
        teamname:'',
        teamimg:'',
        tmid:0,
        isdetail:false,
        chartList:[1,2,3,4,5,6,7,8,9,10],
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {
        //获取排行榜
        let isteam;
        if (options.cid == 2) {
            isteam = true;
            wx.setNavigationBarTitle({
                title: '团队排行榜'
            });
            if (options.istm == 'no'){
                this.setData({
                    istm:true,
                });
            }
        } else {
            isteam = false;
            wx.setNavigationBarTitle({
                title: '个人排行榜'
            });
        }
        this.setData({
            isTeam: isteam,
        });
        this.getRankList(options.cid);
    },

    /**
     * 生命周期函数--监听页面初次渲染完成
     */
    onReady: function () {
        this.runChart();
    },

    /**
     * 生命周期函数--监听页面显示
     */
    onShow: function () {

    },

    /**
     * 生命周期函数--监听页面隐藏
     */
    onHide: function () {

    },

    /**
     * 生命周期函数--监听页面卸载
     */
    onUnload: function () {

    },

    /**
     * 页面相关事件处理函数--监听用户下拉动作
     */
    onPullDownRefresh: function () {

    },

    /**
     * 页面上拉触底事件的处理函数
     */
    onReachBottom: function () {

    },

    /**
     * 用户点击右上角分享
     */
    onShareAppMessage: function (res) {
        let that = this;
        if(that.data.tmid != 0){
            return {
                title: '快来加入我们团队吧',
                path: '/pages/index/index?tmid=' + that.data.tmid,
                success: function (data) {
                    console.log(data)
                },
                fail: function (data) {
                    console.log(data)
                }
            }
        }
    },
    /**
     * 获取排行榜单
     */
    getRankList:function(cid){
        //console.log(cid);
        wx.showLoading({
            title: "加载中",
            mask: true,
        });
        let that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getRankList', cid: cid, openid:wx.getStorageSync('openid') },
            success: function (res) {
                //console.log(res)
                that.setData({
                    rank: res.data.data.rank,
                    rankList: res.data.data.ranklist,
                    tmid: res.data.data.rank.id ? res.data.data.rank.id :0,
                    isDetail: false,
                });
                if (res.data.code == 'no'){
                    wx.showToast({
                        title: res.data.msg,
                        icon: 'none',
                        duration: 2000
                    })
                } 
                setTimeout(function () {
                    wx.hideLoading()
                }, 1000)
            }
        })
    },
    today:function(){
        let that = this;
        that.setData({
            today: true,
            all: false,
        });
        if (that.data.isTeam){
            that.getRankList(2);
        }else{
            that.getRankList(1);
        }
    },
    all: function () {
        let that = this;
        that.setData({
            today: false,
            all: true,
        });
        if (that.data.isTeam) {
            that.getRankList(4);
        } else {
            that.getRankList(3);
        }
    },
    //创建团队
    createTeam:function(){
        this.setData({
            show:true,
        })
    },
    close:function(){
        this.setData({
            show: false,
        })
    },
    //选择图片
    chooseimg:function(){
        let that = this;
        wx.chooseImage({
            count: 1, // 默认9
            sizeType: ['original', 'compressed'], // 可以指定是原图还是压缩图，默认二者都有
            sourceType: ['album', 'camera'], // 可以指定来源是相册还是相机，默认二者都有
            success: function (res) {
                // 返回选定照片的本地文件路径列表，tempFilePath可以作为img标签的src属性显示图片
                that.setData({
                    preimg: res.tempFilePaths[0]
                })
            }
        })
    },
    //验证团队名称
    checkteamname:function(e){
        let that = this;
        if (e.detail.value == ''){
            wx.showToast({
                title: '团队名称不能为空',
                icon: 'none',
                duration: 2000
            })
        }
        that.setData({
            teamname: e.detail.value
        })
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'checkteamname', teamname: e.detail.value},
            success: function (res) {
                if (res.data.code == 'no') {
                    wx.showToast({
                        title: res.data.msg,
                        icon: 'none',
                        duration: 2000
                    })
                }
            }
        });
    },
    //提交
    formSubmit:function(e){
        let that = this;
        if(that.data.teamname == ''){
            wx.showToast({
                title: '团队名称不能为空',
                icon: 'none',
                duration: 2000
            })
            return false;
        }
        if (that.data.preimg == ''){
            wx.showToast({
                title: '请选择团队徽章',
                icon: 'none',
                duration: 2000
            })
            return false;
        }
        wx.uploadFile({
            url: config.service.requestUrl +"?a=createteam", //仅为示例，非真实的接口地址
            filePath: that.data.preimg,
            name: 'file',
            header:{
                'content-type':"multipart/form-data",
            },
            formData: {
                'openid': wx.getStorageSync('openid'),
                'team_name': that.data.teamname,
            },
            success: function (res) {
                var res = JSON.parse(res.data.trim());
                if(res.code == 'ok'){
                    that.setData({
                        show:false,
                        istm:false,
                        tmid: res.data,
                    })
                } 
                wx.showToast({
                    title: res.msg,
                    icon: 'none',
                    duration: 2000
                })
                that.getRankList(2);
            }
        })
    },
    //团队详情
    teamDetail:function(op){
        let that = this;
        if (that.data.tmid != 0 && op.currentTarget.id !=''){
            wx.showLoading({
                title: "加载中",
                mask: true,
            });
            wx.setNavigationBarTitle({
                title: '团队成员',
            })
            wx.request({
                url: config.service.requestUrl,
                data: { a: 'teamDetail', tmid: op.currentTarget.id, },
                success: function (res) {
                    //console.log(res)
                    that.setData({
                        rank: res.data.data.rank,
                        rankList: res.data.data.ranklist,
                        tmid: res.data.data.rank.id ? res.data.data.rank.id : 0,
                        isDetail:true,
                    });
                    if (res.data.code == 'no') {
                        wx.showToast({
                            title: res.data.msg,
                            icon: 'none',
                            duration: 2000
                        })
                    }
                    setTimeout(function () {
                        wx.hideLoading()
                    }, 1000)
                }
            })
        }
    },
    //返回团队榜
    returnList:function(){
        let that = this;
        that.setData({
            isDetail:false,
        });
        wx.setNavigationBarTitle({
            title: '团队排行榜',
        })
        that.getRankList(2);
    },
    //获取图表数据
    getCharts:function(){
        let that = this;
        wx.request({
            url: config.service.requestUrl,
            data: { a: 'getCharts', openid:wx.getStorageSync('openid') },
            success: function (res) {
                console.log(res)
            }
        })
    },
    //图表
    runChart:function(e){
        var that = this;
        var windowWidth = 320;
        try {
            var res = wx.getSystemInfoSync();
            windowWidth = res.windowWidth;
        } catch (e) {
            console.error('getSystemInfoSync failed!');
        }

        lineChart = new wxCharts({
            canvasId: 'lineCanvas',
            type: 'line',
            categories: that.data.chartList,
            animation: false,
            series: [{
                name: '步数',
                data: that.data.chartList ,
                format: function (val, name) {
                    return val + '步';
                }
            }],
            xAxis: {
                disableGrid: false
            },
            yAxis: {
                title: '步数（步）',
                format: function (val) {
                    return val;
                },
                min: 0
            },
            width: windowWidth,
            height: 160,
            dataLabel: true,
            dataPointShape: true,
            enableScroll: true,
            extra: {
                lineStyle: 'curve'
            }
        });
    },
    touchHandler: function (e) {
        lineChart.scrollStart(e);
    },
    moveHandler: function (e) {
        lineChart.scroll(e);
    },
    touchEndHandler: function (e) {
        lineChart.scrollEnd(e);
        lineChart.showToolTip(e, {
            format: function (item, category) {
                return category + ' ' + item.name + ':' + item.data
            }
        });
    },
})