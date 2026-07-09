document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.mlbs-front').forEach(function (wrapper) {
    trackImpressions(wrapper);
    var type = wrapper.getAttribute('data-mlbs-type');
    if (type === 'slideshow') {
      initSlideshow(wrapper);
    } else if (type === 'carousel') {
      initCarousel(wrapper);
    }
  });



  function trackImpressions(wrapper) {
    if (!wrapper || wrapper.getAttribute('data-mlbs-tracked') === '1') {
      return;
    }

    var raw = wrapper.getAttribute('data-mlbs-banner-ids') || '';
    var ids = raw.split(',').map(function (value) {
      return parseInt(value, 10);
    }).filter(function (value) {
      return value > 0;
    });

    if (!ids.length || !mlbsFront.ajaxUrl || !mlbsFront.trackNonce) {
      return;
    }

    wrapper.setAttribute('data-mlbs-tracked', '1');

    var body = new URLSearchParams();
    body.append('action', 'mlbs_track_impression');
    body.append('nonce', mlbsFront.trackNonce);
    ids.forEach(function (id) {
      body.append('banner_ids[]', String(id));
    });

    if (window.fetch) {
      window.fetch(mlbsFront.ajaxUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
        },
        body: body.toString()
      }).catch(function () {});
      return;
    }

    var xhr = new XMLHttpRequest();
    xhr.open('POST', mlbsFront.ajaxUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.send(body.toString());
  }

  function getDurationFromItem(item) {
    if (!item) {
      return mlbsFront.autoplaySpeed || 4000;
    }
    return parseInt(getComputedStyle(item).getPropertyValue('--mlbs-item-duration'), 10) || mlbsFront.autoplaySpeed || 4000;
  }

  function initSlideshow(wrapper) {
    var items = Array.prototype.slice.call(wrapper.querySelectorAll('.mlbs-item'));
    if (!items.length) {
      return;
    }

    var prev = wrapper.querySelector('.mlbs-nav-prev');
    var next = wrapper.querySelector('.mlbs-nav-next');
    var effect = wrapper.getAttribute('data-mlbs-transition') || 'slide';
    var index = 0;
    var timer = null;

    wrapper.classList.add('mlbs-ready');
    items.forEach(function (item, i) {
      item.classList.toggle('is-active', i === 0);
      item.classList.toggle('is-prev', false);
      item.classList.toggle('is-next', false);
    });

    function show(newIndex, direction) {
      if (newIndex === index) {
        return;
      }

      items.forEach(function (item, i) {
        item.classList.remove('is-prev', 'is-next');
        if (i === index && effect === 'slide') {
          item.classList.add(direction === 'prev' ? 'is-next' : 'is-prev');
        }
        item.classList.toggle('is-active', i === newIndex);
      });

      if (effect === 'slide' && items[newIndex]) {
        items[newIndex].classList.add(direction === 'prev' ? 'is-prev' : 'is-next');
        window.setTimeout(function () {
          items[newIndex].classList.remove('is-prev', 'is-next');
          items.forEach(function (item, i) {
            if (i !== newIndex) {
              item.classList.remove('is-prev', 'is-next');
            }
          });
        }, 500);
      }

      index = newIndex;
    }

    function stop() {
      if (timer) {
        window.clearTimeout(timer);
        timer = null;
      }
    }

    function start() {
      if (parseInt(wrapper.getAttribute('data-mlbs-autoplay'), 10) !== 1 || items.length < 2) {
        return;
      }
      stop();
      timer = window.setTimeout(function () {
        go(1);
      }, getDurationFromItem(items[index]));
    }

    function restart() {
      stop();
      start();
    }

    function go(step) {
      var nextIndex = (index + step + items.length) % items.length;
      show(nextIndex, step < 0 ? 'prev' : 'next');
      restart();
    }

    if (prev) {
      prev.addEventListener('click', function () {
        go(-1);
      });
    }

    if (next) {
      next.addEventListener('click', function () {
        go(1);
      });
    }

    if (parseInt(mlbsFront.pauseOnHover, 10) === 1) {
      wrapper.addEventListener('mouseenter', stop);
      wrapper.addEventListener('mouseleave', start);
    }

    start();
  }

  function initCarousel(wrapper) {
    var track = wrapper.querySelector('.mlbs-track');
    var items = Array.prototype.slice.call(wrapper.querySelectorAll('.mlbs-item'));
    if (!track || !items.length) {
      return;
    }

    var prev = wrapper.querySelector('.mlbs-nav-prev');
    var next = wrapper.querySelector('.mlbs-nav-next');
    var timer = null;
    var index = 0;

    function visibleCount() {
      if (window.innerWidth <= 767) {
        return parseInt(wrapper.getAttribute('data-mlbs-mobile'), 10) || 1;
      }
      if (window.innerWidth <= 1024) {
        return parseInt(wrapper.getAttribute('data-mlbs-tablet'), 10) || 1;
      }
      return parseInt(wrapper.getAttribute('data-mlbs-desktop'), 10) || 1;
    }

    function maxIndex() {
      return Math.max(0, items.length - visibleCount());
    }

    function apply() {
      var first = items[0];
      if (!first) {
        return;
      }
      var gap = parseInt(getComputedStyle(wrapper).getPropertyValue('--mlbs-gap'), 10) || mlbsFront.defaultGap || 16;
      var step = first.getBoundingClientRect().width + gap;
      track.style.transform = 'translate3d(' + (-1 * index * step) + 'px,0,0)';
    }

    function stop() {
      if (timer) {
        window.clearTimeout(timer);
        timer = null;
      }
    }

    function start() {
      if (parseInt(wrapper.getAttribute('data-mlbs-autoplay'), 10) !== 1 || items.length < 2) {
        return;
      }
      stop();
      timer = window.setTimeout(function () {
        move(1);
      }, getDurationFromItem(items[Math.min(index, items.length - 1)]));
    }

    function restart() {
      stop();
      start();
    }

    function move(stepDir) {
      var limit = maxIndex();
      if (limit <= 0) {
        index = 0;
      } else {
        index += stepDir;
        if (index > limit) {
          index = 0;
        }
        if (index < 0) {
          index = limit;
        }
      }
      apply();
      restart();
    }

    if (prev) {
      prev.addEventListener('click', function () {
        move(-1);
      });
    }

    if (next) {
      next.addEventListener('click', function () {
        move(1);
      });
    }

    if (parseInt(mlbsFront.pauseOnHover, 10) === 1) {
      wrapper.addEventListener('mouseenter', stop);
      wrapper.addEventListener('mouseleave', start);
    }

    window.addEventListener('resize', function () {
      index = Math.min(index, maxIndex());
      apply();
    });

    apply();
    start();
  }
});
