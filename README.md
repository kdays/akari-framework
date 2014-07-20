Akari Framework
===============

这是一个自我中心的伪PHP框架，用于开发KDays相关应用。

由于在公司写了大量的控制器，所以看到控制器就感觉很微妙。于是这个框架放弃了大部分PHP框架的控制器。而是使用action来进行区分。

主要特性:

- DB支持链式操作，或者自行编写绑定参数
- 模型和服务的划分
- 方便的工具方法
- 模板逻辑分离
- 多语言支持

模板方面支持layout的布局方式。 语法采用自己的方法。 举例:

	<!--#if $a == 3-->
	  a=3
	  <!--#loop $b as $value-->
	  $value
	  <!--#loopend-->
	<!--#endif-->


Akari的意思在日文中有光的意义；此外我们更想引申为摇曳百合中的主角赤座灯里(Akaza Akari)的名称。
我们希望的是这个框架可以默默无闻的为KDays各项应用提供支撑。


如何开始使用?
没有什么比直接看代码更方便的了，你可以通过 [[https://github.com/kdays/akari-framework-start]] 这个初始项目开始工程。
