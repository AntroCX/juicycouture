export const stopPageScrolling = (top) => {
  window.htmlPadding = $(document).scrollTop();

  $('html')
    .attr('data-popup-is-open', '')
    .css({
      'top':    top !== undefined ? top : -window.htmlPadding,
      'height': `calc(100% + ${window.htmlPadding}px)`
    });
};

export const startPageScrolling = () => {
  $('html')
    .removeAttr('data-popup-is-open')
    .css({
      'top':    'initial',
      'height': 'initial'
    });
  $(document).scrollTop(window.htmlPadding);
};