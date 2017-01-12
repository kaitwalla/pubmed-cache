$(function() {
  $('button')
    .on('click',function() {
      switch($(this).data('action')) {
        case 'add':
          modal.show('Add feed','add',false);
        break;
        case 'save':
          ajax_actions.save();
        break;
      }
    });
  
  var ajax_actions = function() {
    var private = {
      get_data_from_modal: function() {
        return_data = {};

        modal_container = modal.get_container();
        return_data.slug = modal_container.find('input[name="slug"]').val();
        return_data.type = modal_container.find('input[type="hidden"]').val();

        if (return_data.type == 'add' || return_data.type == 'update') {
          return_data.name = modal_container.find('input[name="name"]').val();
          return_data.pubmed_url = modal_container.find('input[name="pubmed_url"]').val();
        }
        
        return return_data;
      },
      url: 'inc/actions.php'
    };
    var public = {
      save: function() {
        $.post({
          url: private.url,
          data: private.get_data_from_modal(),
          success: function(data) {
            console.log(data);
          }
        })
      }
    };
    return public;
  }();
  
  var modal = function() {
    var private = {
      container: $('.modal'),
      initialize: function() {
        private.header = private.container.find('h2');
        private.purpose = private.container.find('input[type="hidden"]');
        private.set_slug_typer();
      },
      set_slug_typer: function() {  
        $('.modal').on('keyup','input[name="name"]',function() {
          var slug = $('.modal input[name="name"]').val().toLowerCase().replace(/\W/g,'');
          $('.modal input[name="slug"]').val(slug);
        });
      }
    };
    var public = {
      get_container: function() {
        return private.container;
      },
      show: function(header_text,purpose,data) {
        private.header.text(header_text);
        private.purpose.val(purpose);
        if (!data) {
          private.container.find('input[type="text"]').each(function() {
            $(this).val('');
          });
        } else {

        }
        private.container.modal();
      }
    };
    private.initialize();
    return public;
 }();
 
});