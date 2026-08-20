<div class="min-h-screen bg-black text-white"
x-data="{
draggedGroup:null,draggedBinding:null,
groupOrder(){return [...document.querySelectorAll('[data-nova-group]')].map(el=>Number(el.dataset.novaGroup))},
bindingOrder(groupId){return [...document.querySelectorAll(`[data-nova-binding][data-group-id='${groupId}']`)].map(el=>Number(el.dataset.novaBinding))}
}">
<style>
.ncc{--line:#282828;--muted:#858585;--orange:#ff6a00}.ncc *{box-sizing:border-box}.ncc .wrap{max-width:1500px;margin:auto;padding:26px}
.ncc .head{display:flex;justify-content:space-between;align-items:flex-end;gap:18px;padding-bottom:17px;border-bottom:1px solid var(--line)}.ncc .ey{color:var(--orange);font-size:10px;letter-spacing:.2em;text-transform:uppercase;font-weight:800}
.ncc h1{font-size:28px;margin:5px 0}.ncc .muted{color:var(--muted)}.ncc select,.ncc button,.ncc input{background:#111;color:#eee;border:1px solid #333;border-radius:10px;padding:9px 11px}.ncc button{cursor:pointer}
.ncc .primary{background:var(--orange);border-color:var(--orange);color:#070707;font-weight:800}.ncc .toolbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:17px 0}.ncc .grid{display:grid;grid-template-columns:225px minmax(480px,1fr) 345px;gap:16px}
.ncc .card{background:#0b0b0b;border:1px solid var(--line);border-radius:16px;padding:14px}.ncc .group{display:flex;align-items:center;gap:8px;width:100%;margin:4px 0;text-align:left}.ncc .group.on{border-color:#75431c}.ncc .handle{cursor:grab;color:#666}
.ncc .cap{display:flex;align-items:center;gap:10px;padding:12px;border-bottom:1px solid #202020}.ncc .capname{flex:1;border:0;background:transparent;text-align:left;padding:0;font-weight:700}.ncc .toggle.on{border-color:#76451f;color:#ff9d52}.ncc .toggle.off{opacity:.45}
.ncc .notice{padding:9px 12px;margin:12px 0;background:#0c1a10;border:1px solid #1c5530;color:#71df93;border-radius:10px;font-size:12px}.ncc .editor{margin-top:16px;padding-top:15px;border-top:1px solid #222}
.ncc .field{margin-top:9px}.ncc .field label{display:block;color:#777;font-size:11px;margin-bottom:5px}.ncc .field input{width:100%}.ncc .tools{display:grid;grid-template-columns:1fr 1fr;gap:7px;margin-top:10px}.ncc .tool{text-align:left}.ncc .tool.off{opacity:.35;text-decoration:line-through}
.ncc .preview-group{color:#666;font-size:9px;text-transform:uppercase;letter-spacing:.17em;margin:15px 8px 6px}.ncc .preview-item{background:#111;padding:10px 12px;border-radius:9px;margin:4px 0}
@media(max-width:1100px){.ncc .grid{grid-template-columns:200px 1fr}.ncc .preview{grid-column:1/-1}}
</style>
<div class="ncc"><div class="wrap">
<div class="head">
<div><div class="ey">NOVA Studio</div><h1>Visual Capability Composer</h1><div class="muted">Define el negocio. Elige capacidades. Decide quién las ve y dónde.</div></div>
<div style="display:flex;gap:8px"><button wire:click="exportWorkspace">Exportar workspace</button><button class="primary" wire:click="exportAll">Exportar NOVA Definition</button></div>
</div>

<div class="toolbar">
<span class="muted">Workspace</span>
<select wire:model.live="workspaceId">@foreach($workspaces as $workspace)<option value="{{$workspace->id}}">{{$workspace->name}}</option>@endforeach</select>
<span class="muted">Panel</span>
<select wire:model.live="panelId">@foreach($panels as $p)<option value="{{$p->id}}">{{$p->name}}</option>@endforeach</select>
<span class="muted">Rol</span>
<select wire:model.live="role"><option value="owner">Propietario</option><option value="employee">Empleado</option><option value="manager">Gestión</option></select>
<span class="muted">Salida</span>
<select wire:model.live="representation"><option value="livewire">App · Livewire</option><option value="filament">Filament</option></select>
@if($panel->key==='community')<a href="/comunigest/inicio" target="_blank" style="margin-left:auto;color:#ff8b35">Abrir resultado ↗</a>@endif
</div>

<div class="notice">{{$notice}}</div>

<div class="grid">
<aside class="card">
<div class="ey" style="color:#777">Grupos</div>
<div @drop.prevent="if(draggedGroup!==null){let s=document.querySelector(`[data-nova-group='${draggedGroup}']`),t=$event.target.closest('[data-nova-group]');if(s&&t&&s!==t)t.before(s);$wire.reorderGroups(groupOrder())}">
@foreach($groups as $group)
<button draggable="true" data-nova-group="{{$group->id}}" @dragstart="draggedGroup={{$group->id}}" @dragend="draggedGroup=null" wire:click="selectGroup({{$group->id}})" class="group {{$selectedGroupId===$group->id?'on':''}}">
<span class="handle">⋮⋮</span><span>{{$group->name}}</span>
</button>
@endforeach
</div>
</aside>

<main class="card">
<div class="ey">{{$selectedGroup?->key}}</div><div style="font-size:20px;font-weight:800;margin:5px 0 10px">{{$selectedGroup?->name}}</div>
<div @drop.prevent="if(draggedBinding!==null){let s=document.querySelector(`[data-nova-binding='${draggedBinding}']`),t=$event.target.closest('[data-nova-binding]');if(s&&t&&s!==t)t.before(s);$wire.reorderCapabilities({{$selectedGroup?->id??0}},bindingOrder({{$selectedGroup?->id??0}}))}">
@forelse($bindings as $binding)
<div class="cap" draggable="true" data-nova-binding="{{$binding->id}}" data-group-id="{{$selectedGroup?->id}}" @dragstart="draggedBinding={{$binding->id}}" @dragend="draggedBinding=null">
<span class="handle">⋮⋮</span>
<button class="capname" wire:click="selectCapability({{$binding->capability_id}})">{{$binding->settings['label']??$binding->capability?->name}}<div class="muted" style="font-size:11px">{{$binding->capability?->key}}</div></button>
<button wire:click="toggleCapability({{$binding->id}})" class="toggle {{$binding->visible?'on':'off'}}">{{$binding->visible?'✓ Activa':'— Oculta'}}</button>
</div>
@empty
<div class="muted" style="padding:20px 8px">No hay capacidades para este rol/salida todavía.</div>
@endforelse
</div>

@if($selectedCapability)
<div class="editor">
<div style="display:flex;justify-content:space-between;align-items:center"><div><div class="ey">Editar · {{$selectedCapability->name}}</div><div class="muted" style="font-size:11px">Solo mostramos detalle cuando lo necesitas.</div></div><button wire:click="toggleAdvanced">{{$showAdvanced?'Simple':'Avanzado'}}</button></div>
<div class="field"><label>Label</label><input wire:model="capabilityLabel"></div>
<div class="field"><label>Icono</label><input wire:model="capabilityIcon" placeholder="heroicon-o-..."></div>
<button style="margin-top:9px" wire:click="saveCapabilityPresentation">Guardar presentación</button>
@if($showAdvanced)
<div class="editor"><div class="ey">Herramientas</div><div class="tools">
@foreach($selectedCapability->tools as $tool)
@php
$tb=\App\Models\Nova\NovaBinding::query()->where('panel_id',$panel->id)->where('tool_id',$tool->id)->where('target_type',\App\Enums\Nova\NovaBindingTarget::Tool)->where('role',$role)->where('representation',\App\Enums\Nova\NovaRepresentationType::from($representation))->first();
$te=$tb?$tb->visible:true;
@endphp
<button wire:click="toggleTool({{$tool->id}})" class="tool {{$te?'':'off'}}">{{$te?'●':'○'}} {{$tool->name}}</button>
@endforeach
</div>
<div style="margin-top:13px" class="ey">Recursos</div>
@foreach($selectedCapability->resources as $resource)<div class="preview-item">{{$resource->name}} <span class="muted" style="float:right">{{$resource->type?->value}}</span></div>@endforeach
<div style="margin-top:13px" class="ey">Connectors</div>
@forelse($selectedCapability->connectors as $connector)<div class="preview-item">{{$connector->name}}</div>@empty<div class="muted" style="font-size:12px;margin-top:6px">Sin conectores asociados.</div>@endforelse
</div>
@endif
</div>
@endif
</main>

<aside class="card preview">
<div class="ey">Preview · {{$representation==='filament'?'Filament':'App'}}</div><div style="font-size:18px;font-weight:800;margin:5px 0">{{$role==='owner'?'Propietario':($role==='employee'?'Empleado':'Gestión')}}</div>
<div class="preview-item">⌂ Dashboard</div>
@foreach($previewItems as $groupName=>$items)<div class="preview-group">{{$groupName}}</div>@foreach($items as $item)<div class="preview-item">{{$item->settings['label']??$item->capability?->name}}<span style="float:right;color:#555">›</span></div>@endforeach@endforeach
</aside>
</div>
</div></div>
</div>
