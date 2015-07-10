function appendPrependReplaceText(value) {
    var feedback_txt = document.getElementById("comment_txt").value;
    if (value == 1) {
        $(".comment-text-id").each(function () {
            var feedback = $(this).val();
            $(this).val(feedback + feedback_txt);
        });

    } else if (value == 2) {
        $(".comment-text-id").each(function () {
            var feedback = $(this).val();
            $(this).val(feedback_txt);
        });
    } else if (value == 3) {
        $(".comment-text-id").each(function () {
            var feedback = $(this).val();
            $(this).val(feedback_txt + feedback);
        });
    }
}