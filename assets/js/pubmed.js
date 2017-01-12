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
        case 'delete-item':
          var li = $(this).parents('li');
          if(confirm('Are you sure you want to delete "'+li.children('p').text()+'"?')) {
            ajax_actions.delete(li.data('id'));
          }
        break;
        case 'edit-item':
          var li = $(this).parents('li');
          var data = {
            'id' : li.data('id'),
            'pubmed_url' : li.data('url'),
            'slug' : li.data('slug'),
            'name' : li.children('p').text() 
          };
          modal.show('Edit feed','edit',data);
        break;
        case 'refresh-item':
          var li = $(this).parents('li');
          var data = {
            'type' : 'refresh',
            'id' : li.data('id'),
            'pubmed_url' : li.data('url')
          }
          ajax_actions.refresh(data);
        break;
      }
    });
  
  var ajax_actions = function() {
    var private = {
      add_alert: function(type,message) {
        $('<div class="user-alert alert alert-'+type+' alert-dismissible fade show"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button><p>'+message+'</p></div>').appendTo('body');
      },
      after_actions: {
         alert_update: function(content) {
           var name = $('li[data-id="'+content+'"] p').text();
           private.add_alert('success',name+' updated successfully!');
         },
         add_row: function(content) {
           modal.hide();
           $('<li class="list-group-item" data-id="'+content.id+'" data-slug="'+content.slug+'" data-url="'+content.pubmed_url+'"><p>'+content.name+'</p><div class="ml-auto"><button data-action="edit-item" class="btn btn-warning"><i class="fa fa-pencil"></i></button> <button data-action="refresh-item" class="btn btn-success"><i class="fa fa-refresh"></i></button> <button data-action="delete-item" class="btn btn-danger"><i class="fa fa-remove"></i></button></div></li>').appendTo('ul.list-group');
           private.add_alert('success','Feed added successfully!');
         },
         error: function(content) {
           private.add_alert('danger',content.content);
         },
         remove_row: function(content) {
           $('li[data-id="'+content.id+'"]').fadeOut().promise().done(function() {
            $(this).remove(); 
           });
           private.add_alert('warning','Feed removed!');
         },
         update_row: function(content) {
           modal.hide();
           var li = $('li[data-id="'+content.id+'"]');
           li.data('slug',content.slug);
           li.data('url',content.pubmed_url);
           li.children('p').text(content.name);
           private.add_alert('success','Feed updated');
         }
      },
      get_data_from_modal: function() {
        return_data = {};

        modal_container = modal.get_container();
        return_data.slug = modal_container.find('input[name="slug"]').val();
        return_data.type = modal_container.find('input[name="purpose"]').val();

        if (return_data.type == 'add' || return_data.type == 'edit') {
          return_data.name = modal_container.find('input[name="name"]').val();
          return_data.pubmed_url = modal_container.find('input[name="pubmed_url"]').val();
        }
        
        if(return_data.type == 'edit') {
          return_data.id = modal_container.find('input[name="id"]').val();
        }
        
        return return_data;
      },
      url: 'inc/actions.php'
    };
    var public = {
      delete: function(id) {
        $.post({
          url: private.url,
          data: {type: 'delete', id: id},
          success: function(data) {
            data = JSON.parse(data);
            private.after_actions[data.type](data.content);
          } 
        })
      },
      refresh: function(data) {
        $.post({
          url: private.url,
          data: data,
          success: function(data) {
            data = JSON.parse(data);
            private.after_actions[data.type](data.content);
          }
        })
      },
      save: function() {
        $.post({
          url: private.url,
          data: private.get_data_from_modal(),
          success: function(data) {
            data = JSON.parse(data);
            private.after_actions[data.type](data.content);
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
        private.purpose = private.container.find('input[name="purpose"]');
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
      hide: function() {
        private.container.modal('hide');
      },
      show: function(header_text,purpose,data) {
        private.header.text(header_text);
        private.purpose.val(purpose);
        if (!data) {
          private.container.find('input[type="text"]').each(function() {
            $(this).val('');
          });
        } else {
          var keys = Object.keys(data);
          for (var i = 0; i < keys.length; i++) {
            private.container.find('input[name="'+keys[i]+'"]').val(data[keys[i]]);
          }
        }
        private.container.modal();
      }
    };
    private.initialize();
    return public;
 }();
 
});