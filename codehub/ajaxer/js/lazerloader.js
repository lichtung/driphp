/**
 * 异步延时加载js插件
 * Created by Lin on 2015/5/16.
 */
function Lazerloader(obj){

    this.url = 'source.php';
    this.tag = 'gettotal';//枚举  gettotal  getdata
    this.total = 0;
    this.cur = 0;
    this.size = 10;
    this.containerSelector = 'body';//默认加载到body
    this.firstTplId = '';
    this.secondTplId = '';
    this.delay = 0;

    var pro = Lazerloader.prototype;
    var env = this;

    pro.init = function (obj) {
        for( var x in obj){
            if(x in this) this[x] = obj[x];
        }
        //模板隐藏
        var firsttpl = $("#"+this.firstTplId);
        var secondtpl = $("#"+this.secondTplId);
        firsttpl.length &&  firsttpl.css('display','none');
        secondtpl.length && secondtpl.css('display','none');
    };

    pro.doAjax = function() {
        var dat = null;
        $.ajax({
            type:'POST',
            url:this.url,
            data:{tag:this.tag,cur:this.cur,size:this.size},
            async:false,
            success: function (data) {
                dat = eval("("+data+")");
                env.cur += env.size;
            }
        });
        return dat;
    };

    /*重置*/
    pro.reset = function () {
        this.tag = 'gettotal';
        this.total = 100;
        this.cur = 0;
    };

    pro.loadTemplate = function(tplid,data){
        for(var i=0; i < data.length; i++){
            var element = $('#'+tplid).clone();
            element.css('display','block');
            for(var x in data[i]){
                element.find('.'+x).html(data[i][x]);
            }
            $( this.containerSelector).append(element);
        }

    };

    pro.loadData = function(data){
        if(this.tag == 'gettotal'){
            env.total = data.total;
            this.loadTemplate(this.firstTplId,data.data);
            this.tag = 'getdata';
        }else{
            this.loadTemplate(this.secondTplId,data.data);
        }
    };
    
    pro.autoLoad = function () {
        env.loadData(env.doAjax());
        setTimeout(function () {
            env.loadData(env.doAjax());
            if(env.cur < env.total){
                return env.autoLoad();
            }
        },env.delay);
    };

    /*自动执行*/
    pro.start = function(obj) {
        this.init(obj);
        this.reset();
        this.autoLoad();
        return this;
    };
}

