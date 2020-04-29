$(document).ready(function(){
    let assessVersion = $('input:radio[name=assess-version]:checked').val();

    $('.js-assessmentVersionText').text(assessVersion);
    $('.js-versionOld-input').hide();

    $('.js-version-inputs').on('change', 'input', function(event){
        $('.js-assessmentVersionText').text(event.target.value);
    });

    $('.js-change-default-link').on('click', function(event){
        //remove disabled styles
        $('#versionNew').removeClass("disable-input");
        //hide
        $('.js-change-default-link').hide();
        //show
        $('.js-versionOld-input').show();
    });
});

