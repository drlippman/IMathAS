    $(document).ready(function () {
    tinymce.init({
        selector: "textarea",
        toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
    });

    var i = 1;
    $('.add-more').click(function(e){
        e.preventDefault();
        $(this).before('<input name="file-'+i+'" type="file" id="uplaod-file" /><br><input type="text" size="20" name="description-'+i+'" placeholder="Description"><br>');
        i++;
    });
});




