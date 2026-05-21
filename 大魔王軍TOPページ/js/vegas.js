$(function() {
    $('#mainimg').vegas({
        slides: [
            { src: './images/1.jpg' },
            { src: './images/2.jpg' },
            { src: './images/3.jpg' },
        ],
		transition: 'blur',
		animation: 'kenburns',	
		delay: 6000,
		animationDuration: 10000,
    });
});

//アニメーション置換用URL
//https://vegas.jaysalvat.com/documentation/transitions/