
        function deleteItem(id, type) {
            var html = '<div><p>Are you sure? This will remove your thread.</p></div>';
            $('<div id="dialog"></div>').appendTo('body').html(html).dialog({
                modal: true, title: 'Message', zIndex: 10000, autoOpen: true,
                width: 'auto', resizable: false,
                closeText: "hide",
                buttons: {
                    "Cancel": function () {
                        $(this).dialog('destroy').remove();
                        return false;
                    },
                    "confirm": function () {

                        jQuerySubmit('delete-items-ajax', {id: id, itemType: type}, 'deleteForumSuccess');

                        $(this).dialog('destroy').remove();
                        return true;
                    }
                },
                close: function (event, ui) {
                    $(this).remove();
                }
            });
        }

        function deleteForumSuccess(response)
        {
              window.location.reload();
        }
