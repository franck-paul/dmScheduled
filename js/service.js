/*global $, dotclear */
'use strict';

dotclear.dmScheduledPostsCount = function() {
  var params = {
    f: 'dmScheduledPostsCount',
    xd_check: dotclear.nonce,
  };
  $.get('services.php', params, function(data) {
    if ($('rsp[status=failed]', data).length > 0) {
      // For debugging purpose only:
      // console.log($('rsp',data).attr('message'));
      window.console.log('Dotclear REST server error');
    } else {
      var nb = $('rsp>count', data).attr('ret');
      if (nb != dotclear.dbScheduledPostsCount_Counter) {
        // First pass or counter changed
        var icon = $('#dashboard-main #icons p a[href="posts.php?status=-1"]');
        if (icon.length) {
          // Update count if exists
          var nb_label = icon.children('span.db-icon-title-dm-scheduled');
          if (nb_label.length) {
            nb_label.text(nb);
          }
        } else {
          if (nb != '') {
            // Add full element (link + counter)
            var icon = $('#dashboard-main #icons p a[href="posts.php"]');
            if (icon.length) {
              var xml = ' <a href="posts.php?status=-1"><span class="db-icon-title-dm-scheduled">' + nb + '</span></a>';
              icon.after(xml);
            }
          }
        }
        // Store current counter
        dotclear.dbScheduledPostsCount_Counter = nb;
      }
    }
  });
};

dotclear.dmScheduledCheck = function() {
  var params = {
    f: 'dmScheduledCheck',
    xd_check: dotclear.nonce
  };
  $.get('services.php', params, function(data) {
    if ($('rsp[status=failed]', data).length > 0) {
      // For debugging purpose only:
      // console.log($('rsp',data).attr('message'));
      window.console.log('Dotclear REST server error');
    } else {
      // Refresh list of last scheduled posts
      var args = {
        f: 'dmLastScheduledRows',
        xd_check: dotclear.nonce
      };
      $.get('services.php', args, function(data) {
        if ($('rsp[status=failed]', data).length > 0) {
          // For debugging purpose only:
          // console.log($('rsp',data).attr('message'));
          window.console.log('Dotclear REST server error');
        } else {
          var xml = $('rsp>rows', data).attr('list');
          // Replace current list with the new one
          if ($('#scheduled-posts span.badge').length) {
            $('#scheduled-posts span.badge').remove();
          }
          if ($('#scheduled-posts ul').length) {
            $('#scheduled-posts ul').remove();
          }
          if ($('#scheduled-posts p').length) {
            $('#scheduled-posts p').remove();
          }
          // Add current hour in badge on module
          var now = new Date();
          var time = now.toLocaleTimeString();
          xml = '<span class="badge badge-block badge-info">' + time + '</span>' + xml;
          // Display module content
          $('#scheduled-posts h3').after(xml);
        }
      });
    }
  });
};

dotclear.dmScheduledPostsView = function(line, action) {
  action = action || 'toggle';
  var id = $(line).attr('id').substr(4);
  var li = document.getElementById('dmspe' + id);
  if (!li && (action == 'toggle' || action == 'open')) {
    li = document.createElement('li');
    li.id = 'dmspe' + id;
    li.className = 'expand';
    // Get content
    $.get('services.php', {
      f: 'getPostById',
      id: id,
      post_type: ''
    }, function(data) {
      var rsp = $(data).children('rsp')[0];
      if (rsp.attributes[0].value == 'ok') {
        var content = $(rsp).find('post_display_excerpt').text() + ' ' + $(rsp).find('post_display_content').text();
        if (content) {
          $(li).append(content);
        }
      } else {
        window.alert($(rsp).find('message').text());
      }
    });
    $(line).toggleClass('expand');
    line.parentNode.insertBefore(li, line.nextSibling);
  } else if (li && li.style.display == 'none' && (action == 'toggle' || action == 'open')) {
    $(li).css('display', 'block');
    $(line).addClass('expand');
  } else if (li && li.style.display != 'none' && (action == 'toggle' || action == 'close')) {
    $(li).css('display', 'none');
    $(line).removeClass('expand');
  }
};

$(function() {
  $.expandContent({
    lines: $('#scheduled-posts li.line'),
    callback: dotclear.dmScheduledPostsView
  });
  $('#scheduled-posts ul').addClass('expandable');
  if (dotclear.dmScheduled_Monitor) {
    $('#scheduled-posts').addClass('badgeable');
    // Auto refresh requested : Set 5 minutes interval between two checks for publishing scheduled entries
    dotclear.dmScheduled_Timer = setInterval(dotclear.dmScheduledCheck, 60 * 5 * 1000);
  }
  if (dotclear.dmScheduled_Counter) {
    var icon = $('#dashboard-main #icons p a[href="posts.php"]');
    if (icon.length) {
      // Icon exists on dashboard
      // First pass
      dotclear.dmScheduledPostsCount();
      // Then fired every 60 seconds
      dotclear.dbScheduledPostsCount_Timer = setInterval(dotclear.dmScheduledPostsCount, 60 * 1000);
    }
  }
});
