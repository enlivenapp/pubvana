<?php

namespace App\Controllers\Admin;

use App\Models\WidgetAreaModel;
use App\Models\WidgetInstanceModel;
use App\Models\WidgetModel;
use App\Services\WidgetService;

class Widgets extends BaseAdminController
{
    public function areas(): string
    {
        $theme    = $this->themeService->getActive();
        $areaModel = new WidgetAreaModel();
        $areas    = $theme ? $areaModel->where('theme_id', $theme->id)->findAll() : [];

        $db = db_connect();
        $areaData = [];
        foreach ($areas as $area) {
            $instances = $db->table('widget_instances wi')
                ->select('wi.*, w.name as widget_name, w.folder')
                ->join('widgets w', 'w.id = wi.widget_id')
                ->where('wi.widget_area_id', $area->id)
                ->orderBy('wi.sort_order', 'ASC')
                ->get()->getResultObject();
            $areaData[(int) $area->id] = ['area' => $area, 'instances' => $instances];
        }

        return $this->adminView('widgets/areas', array_merge($this->baseData('Widgets', 'widgets'), [
            'area_data'       => $areaData,
            'available_widgets' => (new WidgetModel())->where('is_active', 1)->findAll(),
        ]));
    }

    public function addToArea()
    {
        $areaId   = (int) $this->request->getPost('widget_area_id');
        $widgetId = (int) $this->request->getPost('widget_id');
        $model    = new WidgetInstanceModel();
        $model->insert([
            'widget_id'      => $widgetId,
            'widget_area_id' => $areaId,
            'sort_order'     => 999,
            'options_json'   => null,
        ]);
        return redirect()->to('/admin/widgets')->with('success', 'Widget added.');
    }

    public function removeFromArea(int $instanceId)
    {
        (new WidgetInstanceModel())->delete($instanceId);
        return redirect()->to('/admin/widgets')->with('success', 'Widget removed.');
    }

    public function configure(int $instanceId): string
    {
        $db = db_connect();
        $instance = $db->table('widget_instances wi')
            ->select('wi.*, w.folder, w.name as widget_name')
            ->join('widgets w', 'w.id = wi.widget_id')
            ->where('wi.id', $instanceId)
            ->get()->getRowObject();

        if (! $instance) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $widget  = (new WidgetService())->getInstance($instance->folder);
        $options = $instance->options_json ? json_decode($instance->options_json, true) : [];
        $form    = $widget ? $widget->renderAdminForm($options) : '<p>No options.</p>';

        return $this->adminView('widgets/configure', array_merge($this->baseData('Configure Widget', 'widgets'), [
            'instance' => $instance,
            'form'     => $form,
        ]));
    }

    public function saveConfig(int $instanceId)
    {
        $options = $this->request->getPost('options') ?? [];
        (new WidgetInstanceModel())->update($instanceId, ['options_json' => json_encode($options)]);
        return redirect()->to('/admin/widgets')->with('success', 'Widget configured.');
    }

    public function reorder()
    {
        $order = $this->request->getPost('order') ?? [];
        $model = new WidgetInstanceModel();
        foreach ($order as $i => $instanceId) {
            $model->update((int) $instanceId, ['sort_order' => $i]);
        }
        return $this->response->setJSON(['success' => true]);
    }
}
