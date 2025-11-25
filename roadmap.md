# Roadmap

These are the things we want to do.

- [ ] Improve the performance of saving data. Now it is done field by field. We need a better way to handle all updated
  attributes at once.
- [ ] Create actionView and actionIndex classes with its view to facilitate developers' work. Include a chapter in
  readme about how to use those actions and a reminder to set up access control in their applications.
- [ ] Add the ability to enable/disable eager loading in the behavior. Default is true
- [ ] Create a trait for ActiveQuery with a method named withCustomFields which joins the value table. Then, ensure
  behavior can benefit from it. Add functional tests for this and explain this in readme. 
