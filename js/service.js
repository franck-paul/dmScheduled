/*global $, dotclear */
'use strict';

dotclear.dmScheduledPostsCount = () => {
  dotclear.services(
    'dmScheduledPostsCount',
    (data) => {
      const response = JSON.parse(data);
      if (response?.success) {
        if (response?.payload.ret) {
          // Replace current counters
          const nb = response.payload.count;
          if (nb !== undefined && nb != dotclear.dbScheduledPostsCount_Counter) {
            // First pass or counter changed
            let icon = $('#dashboard-main #icons p a[href="posts.php?status=-1"]');
            if (icon.length) {
              // Update count if exists
              const nb_label = icon.children('span.db-icon-title-dm-scheduled');
              if (nb_label.length) {
                nb_label.text(nb);
              }
            } else if (nb != '') {
              // Add full element (link + counter)
              icon = $('#dashboard-main #icons p a[href="posts.php"]');
              if (icon.length) {
                const xml = ` <a href="posts.php?status=-1"><span class="db-icon-title-dm-scheduled">${nb}</span></a>`;
                icon.after(xml);
              }
            }
            // Store current counter
            dotclear.dbScheduledPostsCount_Counter = nb;
          }
        }
      } else {
        console.log(dotclear.debug && response?.message ? response.message : 'Dotclear REST server error');
        return;
      }
    },
    (error) => {
      console.log(error);
    },
    true, // Use GET method
    { json: 1 },
  );
};

dotclear.dmScheduledCheck = () => {
  dotclear.services(
    'dmScheduledCheck',
    (data) => {
      const response = JSON.parse(data);
      if (response?.success) {
        if (response?.payload.ret) {
          dotclear.services(
            'dmLastScheduledRows',
            (data) => {
              const response = JSON.parse(data);
              if (response?.success) {
                if (response?.payload.ret) {
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
                  $('#scheduled-posts h3').after(response.payload.list);
                  // Display badge with current time
                  dotclear.badge($('#scheduled-posts'), {
                    id: 'dmsp',
                    value: time,
                    type: 'info',
                  });
                  // Bind every new lines for viewing scheduled post content
                  $.expandContent({
                    lines: $('#scheduled-posts li.line'),
                    callback: dotclear.dmScheduledPostsView,
                  });
                  $('#scheduled-posts ul').addClass('expandable');
                }
              } else {
                console.log(dotclear.debug && response?.message ? response.message : 'Dotclear REST server error');
                return;
              }
            },
            (error) => {
              console.log(error);
            },
            true, // Use GET method
            { json: 1 },
          );
        }
      } else {
        console.log(dotclear.debug && response?.message ? response.message : 'Dotclear REST server error');
        return;
      }
    },
    (error) => {
      console.log(error);
    },
    true, // Use GET method
    { json: 1 },
  );
};

dotclear.dmScheduledPostsView = (line, action = 'toggle', e = null) => {
  if ($(line).attr('id') == undefined) {
    return;
  }

  const postId = $(line).attr('id').substr(4);
  const lineId = `dmspe${postId}`;
  let li = document.getElementById(lineId);

  if (li) {
    $(li).toggle();
    $(line).toggleClass('expand');
  } else {
    // Get content
    dotclear.getEntryContent(
      postId,
      (content) => {
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
      },
      {
        clean: e.metaKey,
        length: 300,
      },
    );
  }
};

$(() => {
  Object.assign(dotclear, dotclear.getData('dm_scheduled'));
  $.expandContent({
    lines: $('#scheduled-posts li.line'),
    callback: dotclear.dmScheduledPostsView,
  });
  $('#scheduled-posts ul').addClass('expandable');
  if (dotclear.dmScheduled_Monitor) {
    // First pass
    dotclear.dmScheduledCheck();
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
