/*global jQuery, dotclear */
'use strict';

dotclear.dmScheduledPostsCount = (icon) => {
  dotclear.services(
    'dmScheduledPostsCount',
    (data) => {
      try {
        const response = JSON.parse(data);
        if (response?.success) {
          if (response?.payload.ret) {
            // Replace current counters
            const nb = response.payload.count;
            if (nb !== undefined && nb !== dotclear.dbScheduledPostsCount_Counter) {
              const href = icon.attr('href');
              const param = `${href.includes('?') ? '&' : '?'}status=-1`;
              const url = `${href}${param}`;
              // First pass or counter changed
              const link = jQuery(`#dashboard-main #icons p a[href="${url}"]`);
              if (link.length) {
                // Update count if exists
                const nb_label = link.children('span.db-icon-title-dm-scheduled');
                if (nb_label.length) {
                  nb_label.text(nb);
                }
              } else if (nb !== '') {
                // Add full element (link + counter)
                const xml = ` <a href="${url}"><span class="db-icon-title-dm-scheduled">${nb}</span></a>`;
                icon.after(xml);
              }
              // Store current counter
              dotclear.dbScheduledPostsCount_Counter = nb;
            }
          }
        } else {
          console.log(dotclear.debug && response?.message ? response.message : 'Dotclear REST server error');
          return;
        }
      } catch (e) {
        console.log(e);
      }
    },
    (error) => {
      console.log(error);
    },
    true, // Use GET method
    { json: 1 },
  );
};

dotclear.dmLastScheduledRows = () => {
  dotclear.services(
    'dmLastScheduledRows',
    (data) => {
      try {
        const response = JSON.parse(data);
        if (response?.success) {
          if (response?.payload.ret) {
            // Replace current list with the new one
            if (jQuery('#scheduled-posts ul').length) {
              jQuery('#scheduled-posts ul').remove();
            }
            if (jQuery('#scheduled-posts p').length) {
              jQuery('#scheduled-posts p').remove();
            }
            // Add current hour in badge on module
            const now = new Date();
            const time = now.toLocaleTimeString();
            // Display module content
            jQuery('#scheduled-posts h3').after(response.payload.list);
            // Display badge with current time
            dotclear.badge(jQuery('#scheduled-posts'), {
              id: 'dmsp',
              value: `<time datetime="${now.toISOString()}">${time}</time>`,
              type: 'info',
            });
            // Bind every new lines for viewing scheduled post content
            dotclear.expandContent({
              lines: document.querySelectorAll('#scheduled-posts li.line'),
              callback: dotclear.dmScheduledPostsView,
            });
            jQuery('#scheduled-posts ul').addClass('expandable');
          }
        } else {
          console.log(dotclear.debug && response?.message ? response.message : 'Dotclear REST server error');
          return;
        }
      } catch (e) {
        console.log(e);
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
      try {
        const response = JSON.parse(data);
        if (response?.success) {
          if (response?.payload.ret) {
            dotclear.dmLastScheduledRows();
          }
        } else {
          console.log(dotclear.debug && response?.message ? response.message : 'Dotclear REST server error');
          return;
        }
      } catch (e) {
        console.log(e);
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
  if (jQuery(line).attr('id') === undefined) {
    return;
  }

  const postId = jQuery(line).attr('id').substring(4);
  const lineId = `dmspe${postId}`;
  let li = document.getElementById(lineId);

  if (li) {
    jQuery(li).toggle();
    jQuery(line).toggleClass('expand');
  } else {
    // Get content
    dotclear.getEntryContent(
      postId,
      (content) => {
        if (content) {
          li = document.createElement('li');
          li.id = lineId;
          li.className = 'expand';
          jQuery(li).append(content);
          jQuery(line).addClass('expand');
          line.parentNode.insertBefore(li, line.nextSibling);
          return;
        }
        jQuery(line).toggleClass('expand');
      },
      {
        clean: e.metaKey,
        length: 300,
      },
    );
  }
};

dotclear.ready(() => {
  Object.assign(dotclear, dotclear.getData('dm_scheduled'));
  dotclear.expandContent({
    lines: document.querySelectorAll('#scheduled-posts li.line'),
    callback: dotclear.dmScheduledPostsView,
  });
  jQuery('#scheduled-posts ul').addClass('expandable');
  if (dotclear.dmScheduled_Monitor) {
    // First pass
    dotclear.dmScheduledCheck();
    // Auto refresh requested : Set interval between two checks for publishing scheduled entries
    dotclear.dmScheduled_Timer = setInterval(dotclear.dmScheduledCheck, (dotclear.dbScheduledPostsCount_Timer || 300) * 1000);
  }
  if (!dotclear.dmScheduled_Counter) {
    return;
  }
  let icon = jQuery('#dashboard-main #icons p a[href="posts.php"]');
  if (!icon.length) {
    icon = jQuery('#dashboard-main #icons p #icon-process-posts-fav');
  }
  if (icon.length) {
    // Icon exists on dashboard
    // First pass
    dotclear.dmScheduledPostsCount(icon);
    // Then fired every x minutes
    dotclear.dbScheduledPostsCount_Timer = setInterval(
      dotclear.dmScheduledPostsCount,
      (dotclear.dmScheduled_Interval || 300) * 1000,
      icon,
    );
  }
});
