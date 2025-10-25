/*global dotclear */
'use strict';

dotclear.ready(() => {
  dotclear.dmScheduled = dotclear.getData('dm_scheduled');

  const viewPost = (line, _action = 'toggle', event = null) => {
    dotclear.dmViewPost(line, 'dmspe', event.metaKey);
  };

  const getCount = (icon) => {
    dotclear.services(
      'dmScheduledPostsCount',
      (data) => {
        try {
          const response = JSON.parse(data);
          if (response?.success) {
            if (response?.payload.ret) {
              // Replace current counters
              const nb = response.payload.count;
              if (nb !== undefined && nb !== dotclear.dmScheduled.counter) {
                const href = icon.getAttribute('href');
                const param = `${href.includes('?') ? '&' : '?'}status=-1`;
                const url = `${href}${param}`;
                // First pass or counter changed
                const link = document.querySelector(`#dashboard-main #icons p a[href="${url}"]`);
                if (link) {
                  // Update count if exists
                  const nb_label = link.querySelector('span.db-icon-title-dm-scheduled');
                  if (nb_label) {
                    nb_label.textContent = nb;
                  }
                } else if (nb !== '') {
                  // Add full element (link + counter)
                  const xml = ` <a href="${url}"><span class="db-icon-title-dm-scheduled">${nb}</span></a>`;
                  icon.insertAdjacentHTML('afterEnd', xml);
                }
                // Store current counter
                dotclear.mbScheduled.counter = nb;
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

  const getRows = () => {
    dotclear.services(
      'dmLastScheduledRows',
      (data) => {
        try {
          const response = JSON.parse(data);
          if (response?.success) {
            if (response?.payload.ret) {
              // Replace current list with the new one
              for (const item of document.querySelectorAll('#scheduled-posts ul')) item.remove();
              for (const item of document.querySelectorAll('#scheduled-posts p')) item.remove();
              // Add current hour in badge on module
              const now = new Date();
              const time = now.toLocaleTimeString();
              // Display module content
              const title = document.querySelector('#scheduled-posts h3');
              title?.insertAdjacentHTML('afterend', response.payload.list);
              // Display badge with current time
              dotclear.badge(document.querySelector('#scheduled-posts'), {
                id: 'dmsp',
                value: `<time datetime="${now.toISOString()}">${time}</time>`,
                type: 'info',
              });
              // Bind every new lines for viewing scheduled post content
              dotclear.expandContent({
                lines: document.querySelectorAll('#scheduled-posts li.line'),
                callback: viewPost,
              });
              for (const item of document.querySelectorAll('#scheduled-posts ul')) item.classList.add('expandable');
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

  const check = () => {
    dotclear.services(
      'dmScheduledCheck',
      (data) => {
        try {
          const response = JSON.parse(data);
          if (response?.success) {
            if (response?.payload.ret) {
              getRows();
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

  dotclear.expandContent({
    lines: document.querySelectorAll('#scheduled-posts li.line'),
    callback: viewPost,
  });
  for (const item of document.querySelectorAll('#scheduled-posts ul')) item.classList.add('expandable');

  if (dotclear.dmScheduled.monitor) {
    // First pass
    check();
    // Auto refresh requested : Set interval between two checks for publishing scheduled entries
    dotclear.dmScheduled.timerCheck = setInterval(check, (dotclear.dmScheduled.interval || 300) * 1000);
  }

  if (!dotclear.dmScheduled.counter) {
    return;
  }

  const icon = document.querySelector('#dashboard-main #icons p #icon-process-posts-fav');
  if (icon) {
    // Icon exists on dashboard
    // First pass
    getCount(icon);
    // Then fired every x minutes
    dotclear.dmScheduled.timerCount = setInterval(getCount, (dotclear.dmScheduled.interval || 300) * 1000, icon);
  }
});
