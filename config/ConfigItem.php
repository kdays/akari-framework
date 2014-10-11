<?php
namespace Akari\config;

/**
 * 用来说明C的定义
 * 举例 C(ConfigItem::templateBaseDir, '');
 *
 * @package Akari\config
 */
Class ConfigItem {

    /**
     * 设定模板的基础目录
     *
     * TemplateHelper
     */
    const templateBaseDir = "templateBaseDir";

    /**
     * 关闭模板的layout布局使用
     *
     * TemplateHelper
     */
    const closeLayout = "closeLayout";

    /**
     * 自定义当前模板调用的layout
     * 此外使用T([], "layoutName")也可以设定
     *
     * TemplateHelper
     */
    const customLayout = "customLayout";

    /**
     * 跳转模板设定
     *
     * MessageHelper
     */
    const jumpTemplate = "jumpTemplate";

}