$(document).ready(function () {
  $(".nav-coasters").mouseenter(function () {
    $(this).find(".dropdown").slideDown();
  });
  $(".nav-coasters").mouseleave(function () {
    $(this).find(".dropdown").slideUp();
  });

  $(".nav-parks").mouseenter(function () {
    $(this).find(".dropdown").slideDown();
  });
  $(".nav-parks").mouseleave(function () {
    $(this).find(".dropdown").slideUp();
  });

  $(".nav-forums").mouseenter(function () {
    $(this).find(".dropdown").slideDown();
  });
  $(".nav-forums").mouseleave(function () {
    $(this).find(".dropdown").slideUp();
  });

  $(".nav-profile").mouseenter(function () {
    $(this).find(".dropdown").slideDown();
  });
  $(".nav-profile").mouseleave(function () {
    $(this).find(".dropdown").slideUp();
  });

  $(".nav-admin").mouseenter(function () {
    $(this).find(".dropdown").slideDown();
  });
  $(".nav-admin").mouseleave(function () {
    $(this).find(".dropdown").slideUp();
  });
});
