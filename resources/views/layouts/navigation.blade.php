<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <div class="max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center gap-2">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                        <x-application-logo class="block h-9 w-9" />
                        <span class="font-semibold text-green-800 hidden 2xl:inline">{{ \App\Models\Organizacion::actual()->nombre }}</span>
                    </a>
                </div>

                <div class="hidden space-x-4 xl:-my-px xl:ms-6 xl:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Panel') }}
                    </x-nav-link>
                    @modulo('inventario')
                    @can('ver-productos')
                    <x-nav-link :href="route('productos.index')" :active="request()->routeIs('productos.*')">
                        {{ __('Productos') }}
                    </x-nav-link>
                    @endcan
                    @can('ver-categorias')
                    <x-nav-link :href="route('categorias.index')" :active="request()->routeIs('categorias.*')">
                        {{ __('Categorías') }}
                    </x-nav-link>
                    @endcan
                    @endmodulo
                    @modulo('clientes')
                    @can('ver-clientes')
                    <x-nav-link :href="route('clientes.index')" :active="request()->routeIs('clientes.*')">
                        {{ __('Clientes') }}
                    </x-nav-link>
                    @endcan
                    @endmodulo
                    @modulo('proveedores')
                    @can('ver-proveedores')
                    <x-nav-link :href="route('proveedores.index')" :active="request()->routeIs('proveedores.*')">
                        {{ __('Proveedores') }}
                    </x-nav-link>
                    @endcan
                    @endmodulo
                    @modulo('compras')
                    @can('ver-compras')
                    <x-nav-link :href="route('compras.index')" :active="request()->routeIs('compras.*')">
                        {{ __('Compras') }}
                    </x-nav-link>
                    @endcan
                    @endmodulo
                    @modulo('ventas')
                    @can('ver-ventas')
                    <x-nav-link :href="route('ventas.index')" :active="request()->routeIs('ventas.*')">
                        {{ __('Ventas') }}
                    </x-nav-link>
                    @endcan
                    @endmodulo
                    @modulo('inventario')
                    @can('ver-kardex')
                    <x-nav-link :href="route('inventario.kardex')" :active="request()->routeIs('inventario.*')">
                        {{ __('Kardex') }}
                    </x-nav-link>
                    @endcan
                    @endmodulo
                    @can('ver-auditoria')
                    <x-nav-link :href="route('auditoria.index')" :active="request()->routeIs('auditoria.*')">
                        {{ __('Auditoría') }}
                    </x-nav-link>
                    @endcan
                    @canany(['gestionar-usuarios', 'gestionar-roles', 'gestionar-modulos', 'gestionar-organizacion'])
                    <x-nav-link :href="route('configuracion.index')" :active="request()->routeIs('configuracion.*') || request()->routeIs('modulos.*')">
                        {{ __('Configuración') }}
                    </x-nav-link>
                    @endcanany
                </div>
            </div>

            <div class="hidden xl:flex xl:items-center xl:ms-6">
                {{-- Campana de notificaciones (Clase 14) --}}
                @php($sinLeer = Auth::user()->unreadNotifications()->count())
                <a href="{{ route('notificaciones.index') }}" class="relative me-4 text-gray-500 hover:text-gray-700" title="Notificaciones">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                    </svg>
                    @if ($sinLeer > 0)
                        <span class="absolute -top-2 -right-2 bg-red-600 text-white text-xs rounded-full h-5 min-w-[1.25rem] px-1 flex items-center justify-center">{{ $sinLeer > 9 ? '9+' : $sinLeer }}</span>
                    @endif
                </a>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        @if (Auth::user()->roles->count() > 1)
                            <div class="px-4 py-2 text-xs text-gray-500">
                                Rol activo: <span class="font-semibold text-green-800">{{ Auth::user()->rolActivo() ?? '—' }}</span>
                            </div>
                            <x-dropdown-link :href="route('rol.seleccionar')">
                                {{ __('Cambiar de rol') }}
                            </x-dropdown-link>
                        @endif
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Perfil') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Cerrar sesión') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center xl:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden xl:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Panel') }}
            </x-responsive-nav-link>
            @modulo('inventario')
            @can('ver-productos')
            <x-responsive-nav-link :href="route('productos.index')" :active="request()->routeIs('productos.*')">
                {{ __('Productos') }}
            </x-responsive-nav-link>
            @endcan
            @can('ver-categorias')
            <x-responsive-nav-link :href="route('categorias.index')" :active="request()->routeIs('categorias.*')">
                {{ __('Categorías') }}
            </x-responsive-nav-link>
            @endcan
            @endmodulo
            @modulo('clientes')
            @can('ver-clientes')
            <x-responsive-nav-link :href="route('clientes.index')" :active="request()->routeIs('clientes.*')">
                {{ __('Clientes') }}
            </x-responsive-nav-link>
            @endcan
            @endmodulo
            @modulo('proveedores')
            @can('ver-proveedores')
            <x-responsive-nav-link :href="route('proveedores.index')" :active="request()->routeIs('proveedores.*')">
                {{ __('Proveedores') }}
            </x-responsive-nav-link>
            @endcan
            @endmodulo
            @modulo('compras')
            @can('ver-compras')
            <x-responsive-nav-link :href="route('compras.index')" :active="request()->routeIs('compras.*')">
                {{ __('Compras') }}
            </x-responsive-nav-link>
            @endcan
            @endmodulo
            @modulo('ventas')
            @can('ver-ventas')
            <x-responsive-nav-link :href="route('ventas.index')" :active="request()->routeIs('ventas.*')">
                {{ __('Ventas') }}
            </x-responsive-nav-link>
            @endcan
            @endmodulo
            @modulo('inventario')
            @can('ver-kardex')
            <x-responsive-nav-link :href="route('inventario.kardex')" :active="request()->routeIs('inventario.*')">
                {{ __('Kardex') }}
            </x-responsive-nav-link>
            @endcan
            @endmodulo
            @can('ver-auditoria')
            <x-responsive-nav-link :href="route('auditoria.index')" :active="request()->routeIs('auditoria.*')">
                {{ __('Auditoría') }}
            </x-responsive-nav-link>
            @endcan
            @canany(['gestionar-usuarios', 'gestionar-roles', 'gestionar-modulos', 'gestionar-organizacion'])
            <x-responsive-nav-link :href="route('configuracion.index')" :active="request()->routeIs('configuracion.*') || request()->routeIs('modulos.*')">
                {{ __('Configuración') }}
            </x-responsive-nav-link>
            @endcanany
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                @if (Auth::user()->roles->count() > 1)
                    <x-responsive-nav-link :href="route('rol.seleccionar')">
                        {{ __('Cambiar de rol') }} ({{ Auth::user()->rolActivo() ?? '—' }})
                    </x-responsive-nav-link>
                @endif
                <x-responsive-nav-link :href="route('notificaciones.index')">
                    {{ __('Notificaciones') }} ({{ Auth::user()->unreadNotifications()->count() }})
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Perfil') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Cerrar sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
