const l = 50;
const ms = 10;
const me = 10;
const mt = 20;
const fs = 16;

function count(str) {
}
function kijun(soshiki,fs,l,ms,me) {
}
function createLine(lxs,lys,lxe,lye,target) {
}
function show4kaiso(soshiki, fs, l, ms, me, mt, target) {
}

var form = document.forms.myform;

form.myfile.addEventListener('change', function(e) {
    // イベント発生時の処理をここに記述
});

var form = document.forms.myform;

form.myfile.addEventListener('change', function(e) {
    // イベント発生時の処理をここに記述
});

function kijun(soshiki,fs,l,ms,me){

    var w1 = 0;
    var w2 = 0;
    var w3 = 0;
    var w4 = 0;
    var tmp1 = 0;
    var tmp2 = 0;
    var tmp3 = 0;
    var x,y;

    //w1を求める
    w1=count(soshiki['最上階層']['部署名']) * fs / 2;

    //w2を求める
    for(var i=0;i<soshiki['最上階層']['直下部署'].length;i++)
    {
        tmp1=count(soshiki['最上階層']['直下部署'][i]['部署名']) * fs / 2;
            if(tmp1>=w2){
                w2=tmp1;
            }
        
        //w3を求める
        for(var j=0;j<soshiki['最上階層']['直下部署'][i]['直下部署'].length;j++)
        {
            tmp2=count(soshiki['最上階層']['直下部署'][i]['直下部署'][j]['部署名']) *fs/2;
            if(tmp2>=w3){
                w3=tmp2;
            }
            for(var k=0;k<soshiki['最上階層']['直下部署'][i]['直下部署'][j]['直下部署'].length;k++)
            {
                tmp3=count(soshiki['最上階層']['直下部署'][i]['直下部署'][j]['直下部署'][k]['部署名']) *fs/2;
                if(tmp3>=w4){
                    w4=tmp3;
                }
            }
        }
    } 
    
    x= (ms + w1 + me) + l +
       (ms + w2 + me) + l +
       (ms + w3 + me) + l +
       ms;
    
    y=fs;

    return [x,y,w1,w2,w3,w4];
    
}

function createLine(lxs,lys,lxe,lye,target){

    var element = document.getElementById(target);
    var ctx = element.getContext("2d");

    ctx.beginPath();

    ctx.moveTo(lxs,lys);

    ctx.lineTo(lxe,lye);

    ctx.strokeStyle="black";

    ctx.linewidth=10;

    ctx.stroke();
}

function show4kaiso(soshiki,fs,l,ms,me,mt,target){
    var element = document.getElementById(target);
    var ctx = element.getContext("2d");
    ctx.font = `${fs}px serif`;
    var count = 0;

    const ps=3;
    const pe=5;
    const pt=3;
    const pb=6;
    const w4=kijun(soshiki,fs,l,ms,me)[5];

    for (var i=0;i<soshiki['最上階層']['直下部署'].length;i++){
            for(var j=0;j<soshiki['最上階層']['直下部署'][i]['直下部署'].length;j++){
                for(var k=0;k<soshiki['最上階層']['直下部署'][i]['直下部署'][j]['直下部署'].length;k++){
                    var b4xs=kijun(soshiki,fs,l,ms,me)[0];
                    var b4ys=kijun(soshiki,fs,l,ms,me)[1]+(fs + mt)*count + pt;
                    count++;
                    
                    //4階層の部署名を表示
                    ctx.fillText(soshiki['最上階層']['直下部署'][i]['直下部署'][j]['直下部署'][k]['部署名'],b4xs,b4ys);

                    if (soshiki['最上階層']['直下部署'][i]['直下部署'][j]['直下部署'][k]['ISO']=="対象" && soshiki['最上階層']['直下部署'][i]['直下部署'][j]['直下部署'][k]['IATF']=="対象"){
                        ctx.lineJoin = 'round';
                        ctx.strokeRect(b4xs -ps,b4ys - (fs+pt),(w4 + pe),(ps + fs + pb));
                    }else if(soshiki['最上階層']['直下部署'][i]['直下部署'][j]['直下部署'][k]['IATF']=="対象"){
                        ctx.lineJoin = 'round';
                        ctx.setLineDash([2,2]);
                        ctx.strokeRect(b4xs -ps,b4ys - (fs+pt),(w4+pe),(ps + fs + pb));
                        ctx.setLineDash([0,0])
                    }
                    
                    
                    if(k==0){var h4ys0 = b4ys-fs/2;}
                    var h4xs=b4xs-(ms+l/2);
                    var h4xe=b4xs-ms;
                    var h4ys=b4ys-fs/2;
                    var h4ye=b4ys-fs/2;

                    //4階層目の左に横線を表示
                    createLine(h4xs,h4ys,h4xe,h4ye,target);
                }
                var v3xs=kijun(soshiki,fs,l,ms,me)[0] - (ms + l/2);
                var v3ys=h4ys0;
                var v3xe=kijun(soshiki,fs,l,ms,me)[0] -(ms + l/2);
                var v3ye=h4ys;

                //3階層の右に縦線を表示
                createLine(v3xs,v3ys,v3xe,v3ye,target);

                var h3xs = v3xs - l/2;
                var h3ys = (v3ys + v3ye) / 2;
                var h3xe = v3xs;
                var h3ye = (v3ys + v3ye) / 2;

                //3階層の右に横線を表示
                createLine(h3xs,h3ys,h3xe,h3ye,target);

                var b3xs = h3xs - (kijun(soshiki,fs,l,ms,me)[4] + me);
                var b3ys =h3ys + fs/2;

                //3階層の部署名を表示
                ctx.fillText(soshiki['最上階層']['直下部署'][i]['直下部署'][j]['部署名'],b3xs,b3ys);

                if(j==0){var h3ys0=b3ys-fs/2};
                var h3xs=b3xs-(ms+l/2);
                var h3xe=b3xs-ms;
                var h3ys=b3ys-fs/2;
                var h3ye=b3ys-fs/2;

                //3階層目の左に横線を表示
                createLine(h3xs,h3ys,h3xe,h3ye,target); 
            }

            var v2xs=h3xs;
            var v2ys=h3ys0;
            var v2xe=h3xs;
            var v2ye=h3ys;

            //2階層の右に縦線を表示
            createLine(v2xs,v2ys,v2xe,v2ye,target);

            var h2xs = v2xs - l/2;
            var h2ys = (v2ys + v2ye) / 2;
            var h2xe = v2xs;
            var h2ye = (v2ys + v2ye) / 2;

            //2階層の右に横線を表示
            createLine(h2xs,h2ys,h2xe,h2ye,target);

            var b2xs = h2xs - (kijun(soshiki,fs,l,ms,me)[3] + me);
            var b2ys =h2ys + fs/2;

            //2階層の部署名を表示
            ctx.fillText(soshiki['最上階層']['直下部署'][i]['部署名'],b2xs,b2ys);

            if(i==0){var h2ys0=b2ys-fs/2};
            var h2xs=b2xs-(ms+l/2);
            var h2xe=b2xs-ms;
            var h2ys=b2ys-fs/2;
            var h2ye=b2ys-fs/2;

            //2階層目の左に横線を表示
            createLine(h2xs,h2ys,h2xe,h2ye,target); 
        }

        var v1xs=h2xs;
        var v1ys=h2ys0;
        var v1xe=h2xs;
        var v1ye=h2ys;

        //1階層の右に縦線を表示
        createLine(v1xs,v1ys,v1xe,v1ye,target);

        var h1xs = v1xs - l/2;
        var h1ys = (v1ys + v1ye) / 2;
        var h1xe = v1xs;
        var h1ye = (v1ys + v1ye) / 2;

        //2階層の右に横線を表示
        createLine(h1xs,h1ys,h1xe,h1ye,target);

        var b1xs = h1xs - (kijun(soshiki,fs,l,ms,me)[2] + me);
        var b1ys =h1ys + fs/2;

        //1階層の部署名を表示
        ctx.fillText(soshiki['最上階層']['部署名'],b1xs,b1ys);

}

if (soshiki['最上階層']['直下部署'][i]['直下部署'][j]['直下部署'][k]['分類']==1){
                        ctx.lineJoin = 'round';
                        ctx.strokeRect(b4xs -ps,b4ys - (fs+pt),(w4 + pe),(ps + fs + pb));
                    }else if(soshiki['最上階層']['直下部署'][i]['直下部署'][j]['直下部署'][k]['分類']==2){
                        ctx.lineJoin = 'round';
                        ctx.setLineDash([2,2]);
                        ctx.strokeRect(b4xs -ps,b4ys - (fs+pt),(w4+pe),(ps + fs + pb));
                        ctx.setLineDash([0,0])
                    }

