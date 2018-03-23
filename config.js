/**
 * [module]小程序配置文件
 */

// 此处主机域名
// var host = 'https://grbcs.mai022.com';
var host = 'https://hygs.web.mai022.com';
var config = {

    // 下面的地址配合云端 Demo 工作
    service: {
        //主机地址
        host,

        // 微信解密地址
        decodeUrl: `${host}/wxapp/wxdecode.php`,

        // 数据请求地址
        requestUrl: `${host}/wxapp/index.php`,

        //静态资源路径
        imageUrl: `${host}/wxapp/img/`,

    }
};

module.exports = config;