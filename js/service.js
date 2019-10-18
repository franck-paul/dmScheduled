/*global $, dotclear */
'use strict';

dotclear.dmScheduledPostsCount = function() {
  $.get('services.php', {
      f: 'dmScheduledPostsCount',
      xd_check: dotclear.nonce,
    })
    .done(function(data) {
      if ($('rsp[status=failed]', data).length > 0) {
        // For debugging purpose only:
        // console.log($('rsp',data).attr('message'));
        window.console.log('Dotclear REST server error');
      } else {
        const nb = $('rsp>count', data).attr('ret');
        if (nb != dotclear.dbScheduledPostsCount_Counter) {
          // First pass or counter changed
          let icon = $('#dashboard-main #icons p a[href="posts.php?status=-1"]');
          if (icon.length) {
            // Update count if exists
            const nb_label = icon.children('span.db-icon-title-dm-scheduled');
            if (nb_label.length) {
              nb_label.text(nb);
            }
          } else {
            if (nb != '') {
              // Add full element (link + counter)
              icon = $('#dashboard-main #icons p a[href="posts.php"]');
              if (icon.length) {
                const xml = ` <a href="posts.php?status=-1"><span class="db-icon-title-dm-scheduled">${nb}</span></a>`;
                icon.after(xml);
              }
            }
          }
          // Store current counter
          dotclear.dbScheduledPostsCount_Counter = nb;
        }
      }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
      window.console.log(`AJAX ${textStatus} (status: ${jqXHR.status} ${errorThrown})`);
    })
    .always(function() {
      // Nothing here
    });
};

dotclear.dmScheduledCheck = function() {
  $.get('services.php', {
      f: 'dmScheduledCheck',
      xd_check: dotclear.nonce
    })
    .done(function(data) {
      if ($('rsp[status=failed]', data).length > 0) {
        // For debugging purpose only:
        // console.log($('rsp',data).attr('message'));
        window.console.log('Dotclear REST server error');
      } else {
        // Refresh list of last scheduled posts
        $.get('services.php', {
            f: 'dmLastScheduledRows',
            xd_check: dotclear.nonce
          })
          .done(function(data) {
            if ($('rsp[status=failed]', data).length > 0) {
              // For debugging purpose only:
              // console.log($('rsp',data).attr('message'));
              window.console.log('Dotclear REST server error');
            } else {
              const xml = $('rsp>rows', data).attr('list');
              // Replace current list with the new one
              if ($('#scheduled-posts ul').length) {
                $('#scheduled-posts ul').remove();
              }
              if ($('#scheduled-posts p').length) {
                $('#scheduled-posts p').remove();
              }
              // Add current hour in badge on module
              const now = new Date();
              const time = now.toLocaleTimeString();
              // Display module content
              $('#scheduled-posts h3').after(xml);
              // Display badge with current time
              dotclear.badge(
                $('#scheduled-posts'), {
                  id: 'dmsp',
                  value: time,
                  type: 'info'
                }
              );
              // Bind every new lines for viewing scheduled post content
              $.expandContent({
                lines: $('#scheduled-posts li.line'),
                callback: dotclear.dmScheduledPostsView
              });
              $('#scheduled-posts ul').addClass('expandable');
            }
          })
          .fail(function(jqXHR, textStatus, errorThrown) {
            window.console.log(`AJAX ${textStatus} (status: ${jqXHR.status} ${errorThrown})`);
          })
          .always(function() {
            // Nothing here
          });
      }
    })
    .fail(function(jqXHR, textStatus, errorThrown) {
      window.console.log(`AJAX ${textStatus} (status: ${jqXHR.status} ${errorThrown})`);
    })
    .always(function() {
      // Nothing here
    });
};

dotclear.dmScheduledPostsView = function(line, action, e) {
  action = action || 'toggle';
  if ($(line).attr('id') == undefined) {
    return;
  }

  const postId = $(line).attr('id').substr(4);
  const lineId = `dmspe${postId}`;
  let li = document.getElementById(lineId);

  if (!li) {
    // Get content
    dotclear.getEntryContent(postId, function(content) {
      if (content) {
        li = document.createElement('li');
        li.id = lineId;
        li.className = 'expand';
        $(li).append(content);
        $(line).addClass('expand');
        line.parentNode.insertBefore(li, line.nextSibling);
      } else {
        $(line).toggleClass('expand');
      }
    }, {
      clean: (e.metaKey),
      length: 300
    });
  } else {
    $(li).toggle();
    $(line).toggleClass('expand');
  }
};

$(function() {
  $.expandContent({
    lines: $('#scheduled-posts li.line'),
    callback: dotclear.dmScheduledPostsView
  });
  $('#scheduled-posts ul').addClass('expandable');
  if (dotclear.dmScheduled_Monitor) {
    // Auto refresh requested : Set 5 minutes interval between two checks for publishing scheduled entries
    dotclear.dmScheduled_Timer = setInterval(dotclear.dmScheduledCheck, 60 * 5 * 1000);
  }
  if (dotclear.dmScheduled_Counter) {
    const icon = $('#dashboard-main #icons p a[href="posts.php"]');
    if (icon.length) {
      // Icon exists on dashboard
      // First pass
      dotclear.dmScheduledPostsCount();
      // Then fired every 60 seconds
      dotclear.dbScheduledPostsCount_Timer = setInterval(dotclear.dmScheduledPostsCount, 60 * 1000);
    }
  }
});
