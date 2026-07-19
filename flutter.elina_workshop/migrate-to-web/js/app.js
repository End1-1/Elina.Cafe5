(function () {
  Router.register('login', Screens.login);
  Router.register('home', Screens.home);
  Router.register('task', Screens.task);
  Router.register('workshops', Screens.workshops);
  Router.register('journal', Screens.journal);
  Router.register('employees', Screens.employees);
  Router.register('employees-of-day', Screens.employees_of_day);
  Router.register('list-task-works', Screens.list_task_works);
  Router.register('work-details', Screens.work_details);
  Router.register('work-details-done', Screens.work_details_done);

  Router.start();
})();
